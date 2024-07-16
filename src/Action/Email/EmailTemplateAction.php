<?php

namespace App\Action\Email;

use App\Action\Job\JobApplicationInterface;
use App\Controller\Core\Services;
use App\Controller\Email\EmailTemplateController;
use App\DTO\Frontend\JobOffer\Filter\FilterDTO;
use App\Entity\Email\EmailTemplate;
use App\Entity\Security\User;
use App\Enum\Service\Serialization\SerializerType;
use App\Exception\Lib\HtmlToImageException;
use App\Exception\Security\OtherUserResourceAccessException;
use App\Form\Email\EmailTemplateForm;
use App\Repository\Email\EmailTemplateRepository;
use App\Response\Base\BaseResponse;
use App\Response\Email\GetTemplates;
use App\Response\Email\ReFetchTemplate;
use App\Response\Email\SaveTemplate;
use App\Response\Email\Template\GetVariables;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Api\JobSearcher\Provider\DummyOfferProvider;
use App\Service\Email\EmailTemplateService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\System\Restriction\EmailTemplateRestrictionService;
use App\Service\System\Restriction\EmailTemplateTestSendingRestrictionService;
use App\Service\Templates\Variable\VariableProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Routes / endpoints for handling the email builder - template - based actions
 */
class EmailTemplateAction extends AbstractController
{
    public function __construct(
        private Services                                            $services,
        private emailTemplateController                             $emailTemplateController,
        private JwtAuthenticationService                            $jwtAuthenticationService,
        private TranslatorInterface                                 $translator,
        private readonly DummyOfferProvider                         $dummyJobOfferProvider,
        private readonly VariableProvider                           $variableProvider,
        private readonly JobSearchService                           $jobSearchService,
        private readonly ObjectSerializerService                    $objectSerializerService,
        private readonly EmailTemplateRepository                    $emailTemplateRepository,
        private readonly EntityManagerInterface                     $entityManager,
        private readonly LoggerInterface                            $logger,
        private readonly EmailTemplateRestrictionService            $emailTemplateRestrictionService,
        private readonly EmailTemplateService                       $emailTemplateService,
        private readonly EmailTemplateTestSendingRestrictionService $emailTemplateTestSendingRestrictionService,
    )
    {}

    /**
     * Will return all templates that can be cloned and are not assigned to user
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("/email/templates/get-all-clone-able", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getAllCloneAble(): JsonResponse
    {
        $allTemplates  = $this->emailTemplateRepository->getAllCloneAble();
        $templatesData = array_map(
            fn(EmailTemplate $template) => $this->services->getObjectSerializerService()->toArray($template),
            $allTemplates
        );

        $response = GetTemplates::buildOkResponse();
        $response->setTemplatesDataArray($templatesData);

        return $response->toJsonResponse();
    }

    /**
     * Will clone the template
     *
     * @param EmailTemplate $emailTemplate
     *
     * @return JsonResponse
     *
     */
    #[Route("/email/templates/clone/{id}", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function clone(EmailTemplate $emailTemplate): JsonResponse
    {
        if ($this->emailTemplateRestrictionService->hasReachedMaxTemplates()) {
            $msg = $this->translator->trans('email.builder.action.save.maxReached');
            return BaseResponse::buildBadRequestErrorResponse($msg)->toJsonResponse();
        }

        if (!is_null($emailTemplate->getUser())) {
            throw new LogicException("This template is not clone-able: {$emailTemplate->getId()}");
        }

        $user = $this->services->getJwtAuthenticationService()->getUserFromRequest();

        $newTemplateName = uniqid("{$emailTemplate->getEmailTemplateName()}-");

        $bodyContentArray                 = json_decode($emailTemplate->getBody(), true);
        $bodyContentArray['templateName'] = $newTemplateName;
        $changedBodyContent               = json_encode($bodyContentArray);

        $clonedTemplate = clone $emailTemplate;
        $clonedTemplate->setUser($user);
        $clonedTemplate->setEmailTemplateName($newTemplateName);
        $clonedTemplate->setBody($changedBodyContent);

        $user->addEmailTemplate($clonedTemplate);
        $this->entityManager->persist($clonedTemplate);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $msg = $this->translator->trans('email.builder.action.clone.ok', [
            '%template_name%' => $newTemplateName,
        ]);

        return BaseResponse::buildOkResponse($msg)->toJsonResponse();
    }

    /**
     * Will return all templates that belong to given user
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("/email/templates/get-all-for-user", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getAllForUser(): JsonResponse
    {
        $user          = $this->services->getJwtAuthenticationService()->getUserFromRequest();
        $allTemplates  = $this->emailTemplateRepository->getAll($user);
        $templatesData = array_map(
            fn(EmailTemplate $template) => $this->services->getObjectSerializerService()->toArray($template),
            $allTemplates
        );

        $response = GetTemplates::buildOkResponse();
        $response->setTemplatesDataArray($templatesData);

        return $response->toJsonResponse();
    }

    /**
     * Will delete the template for id
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route("/email/templates/delete/{id}", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function delete(int $id): JsonResponse
    {
        // getting user to make sure that user deletes his own templates only, no malformed call etc
        $user = $this->jwtAuthenticationService->getUserFromRequest();

        try {
            $this->emailTemplateRepository->softDeleteById(
                $id,
                $user,
                $this->jwtAuthenticationService->isAnyGrantedToUser([User::ROLE_DEVELOPER])
            );
        } catch (Exception $e) {
            $this->logger->warning("Could not delete the entity for id: {$id}", [
                "targetEntity" => EmailTemplate::class,
                "exception"    => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTrace(),
                ]
            ]);
        }

        $message     = $this->translator->trans('email.builder.action.delete.ok');
        $responseDto = BaseResponse::buildOkResponse($message);
        return $responseDto->toJsonResponse();
    }

    /**
     * Will save the template:
     * - either creates new one,
     * - or updates existing
     *
     * @param Request $request
     * @param bool    $isPredefined
     *
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    #[Route("/email/templates/save/{isPredefined}", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function save(Request $request, bool $isPredefined = false): JsonResponse
    {
        $successMessage = $this->services->getTranslator()->trans('email.builder.action.save.ok');

        $form = $this->createForm(EmailTemplateForm::class);
        $form = $this->services->getFormService()->handlePostFormForAxiosCall($form, $request);
        $user = $this->services->getJwtAuthenticationService()->getUserFromRequest();

        /** @var EmailTemplate $emailTemplateFromForm */
        $emailTemplateFromForm = $form->getData();
        try {
            $base64ExampleSmall = $this->emailTemplateService->prepareExampleImageBase64($emailTemplateFromForm);
        } catch (HtmlToImageException $hie) {
            $msg = $this->translator->trans('email.builder.action.save.htmlToImageFailed.maybeCouldNotGetResources');
            $this->logger->critical("Failed generating E-Mail template preview, error: {$hie->getMessage()}. Trace: {$hie->getTraceAsString()}");
            return SaveTemplate::buildBadRequestErrorResponse($msg)->toJsonResponse();
        }

        $emailTemplateFromForm->setExampleBase64($base64ExampleSmall);

        $violationDto = $this->services->getValidationService()->validateAndReturnArrayOfInvalidFieldsWithMessages($emailTemplateFromForm);
        if( !$violationDto->isSuccess() ){
            return SaveTemplate::buildInvalidFieldsRequestErrorResponse($violationDto->getViolationsWithMessages())->toJsonResponse();
        }

        /**
         * For updating: entity needs to be fetched from DB else upon saving new entry will be created - even if id is supplied
         */
        if( !empty($emailTemplateFromForm->getId()) ){
            $emailTemplateEntity = $this->emailTemplateController->findOneById($emailTemplateFromForm->getId());
            if (is_null($emailTemplateEntity)) {
                throw new Exception("No E-mail template was found for id: {$emailTemplateFromForm->getId()}");
            }

            $emailTemplateEntity->ensureBelongsToUser($user);
            return $this->updateEmail($emailTemplateEntity, $emailTemplateFromForm, $successMessage);
        }

        if ($this->emailTemplateRestrictionService->hasReachedMaxTemplates()) {
            $msg = $this->translator->trans('email.builder.action.save.maxReached');
            return SaveTemplate::buildBadRequestErrorResponse($msg)->toJsonResponse();
        }

        $uniqueNameViolation = $this->emailTemplateController->buildNotUniqueNameViolation($emailTemplateFromForm);
        if( !empty($uniqueNameViolation) ){
            return SaveTemplate::buildInvalidFieldsRequestErrorResponse($uniqueNameViolation)->toJsonResponse();
        }

        if (!$isPredefined) {
            $emailTemplateFromForm->setUser($user);
        } else {
            if (!$this->jwtAuthenticationService->isAnyGrantedToUser([User::ROLE_DEVELOPER])) {
                throw new Exception("User tried to add predefined email template, but he has no rights for it!. User: {$user->getId()}");
            }
        }

        $this->emailTemplateController->save($emailTemplateFromForm);

        $response = SaveTemplate::buildOkResponse($successMessage);
        $response->setTemplateId($emailTemplateFromForm->getId());

        return $response->toJsonResponse();
    }

    /**
     * Will return one email template for the id and md5 or null if the template content has not changed
     *  md5 is used to determine if the template content has changed or not
     *
     * @param int    $id
     * @param string $bodyMd5
     * @param string $titleMd5
     *
     * @return JsonResponse
     *
     * @throws OtherUserResourceAccessException
     */
    #[Route("/email/re-fetch/{id}/{bodyMd5}/{titleMd5}", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function reFetch(int $id, string $bodyMd5, string $titleMd5): JsonResponse
    {
        $emailTemplate = $this->emailTemplateController->findOneById($id);
        if (empty($emailTemplate)) {
            return ReFetchTemplate::buildNotFoundResponse()->toJsonResponse();
        }

        $emailTemplate->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        $currentBodyMd5  = md5($emailTemplate->getBody());
        $currentTitleMd5 = md5($emailTemplate->getSubject());

        if(
                $currentBodyMd5  === $bodyMd5
            &&  $currentTitleMd5 === $titleMd5
        ){
            return ReFetchTemplate::buildOkResponse()->toJsonResponse();
        }

        $templateJson = $this->services->getObjectSerializerService()->toJson($emailTemplate);
        $response     = ReFetchTemplate::buildOkResponse();
        $response->setEmailTemplate($templateJson);

        return $response->toJsonResponse();
    }

    /**
     * Returns dummy offer for email template
     *
     * @return JsonResponse
     */
    #[Route("/email/templates/get-dummy-variables", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getDummyVariables(): JsonResponse
    {
        $dummyAnalysedOffer    = $this->dummyJobOfferProvider->provide();
        $allVariables          = $this->variableProvider->provide($dummyAnalysedOffer);
        $allVariablesDataArray = $allVariables->toArray();

        $response = GetVariables::buildOkResponse();
        $response->setVariablesData($allVariablesDataArray);

        return $response->toJsonResponse();
    }

    /**
     * Will return the real Email Template editor variables used for:
     * - replacing the `var placeholder` with real data (is delivered right here)
     *
     * @param int     $externalOfferId
     * @param Request $request
     *
     * @return JsonResponse
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/email/templates/get-template-variables/{externalOfferId}", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function getRealVariables(int $externalOfferId, Request $request): JsonResponse
    {
        $requestJson       = $request->getContent();
        $requestData       = json_decode($requestJson, true);
        $filterValuesArray = $requestData['filterValues'];
        $filterValuesJson  = json_encode($filterValuesArray);

        /** @var FilterDTO $frontendFilter */
        $frontendFilter = $this->objectSerializerService->fromJson($filterValuesJson, FilterDTO::class, SerializerType::CUSTOM);
        $frontendFilter->selfCorrect();

        $analysedOffer = $this->jobSearchService->getSingleOffer($externalOfferId, $frontendFilter);

        $allVariables          = $this->variableProvider->provide($analysedOffer);
        $allVariablesDataArray = $allVariables->toArray();

        $response = GetVariables::buildOkResponse();
        $response->setVariablesData($allVariablesDataArray);

        return $response->toJsonResponse();
    }

    /**
     * Triggers sending test E-Mail for template
     *
     * @param EmailTemplate $emailTemplate
     * @param Request       $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("/email/templates/send-test/{id}", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function sendTestEmail(EmailTemplate $emailTemplate, Request $request): JsonResponse
    {
        $isJsonValid = $this->services->getValidationService()->validateJson($request->getContent());
        if (!$isJsonValid) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();

        $emailTemplate->ensureBelongsToUser($user);

        if (!$this->emailTemplateTestSendingRestrictionService->isAllowed()) {
            $message = $this->translator->trans('user.sendTemplateTestEmail.limitReached');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        $dataArray = json_decode($request->getContent(), true);
        $body      = $dataArray[JobApplicationInterface::EMAIL_BODY];
        $msg       = $this->translator->trans('user.sendTemplateTestEmail.emailWillBeSoonSent');

        $this->emailTemplateService->createTestEmail($body, $emailTemplate, $user);

        return (BaseResponse::buildOkResponse($msg))->toJsonResponse();
    }

    /**
     * Handle updating existing template
     *
     * @param EmailTemplate|null $emailTemplateEntity
     * @param EmailTemplate      $emailTemplateFromForm
     * @param string             $msg
     *
     * @return JsonResponse
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateEmail(?EmailTemplate $emailTemplateEntity, EmailTemplate $emailTemplateFromForm, string $msg): JsonResponse
    {
        $emailTemplateEntity->setBody($emailTemplateFromForm->getBody());
        $emailTemplateEntity->setEmailTemplateName($emailTemplateFromForm->getEmailTemplateName());
        $emailTemplateEntity->setSubject($emailTemplateFromForm->getSubject());
        $emailTemplateEntity->setExampleHtml($emailTemplateFromForm->getExampleHtml());
        $emailTemplateEntity->setExampleBase64($emailTemplateFromForm->getExampleBase64());

        $uniqueNameViolation = $this->emailTemplateController->buildNotUniqueNameViolation($emailTemplateFromForm, [$emailTemplateFromForm->getId()]);
        if( !empty($uniqueNameViolation) ){
            return SaveTemplate::buildInvalidFieldsRequestErrorResponse($uniqueNameViolation)->toJsonResponse();
        }

        $this->emailTemplateController->save($emailTemplateEntity);

        $response = SaveTemplate::buildOkResponse($msg);
        $response->setTemplateId($emailTemplateEntity->getId());

        return $response->toJsonResponse();
    }

}