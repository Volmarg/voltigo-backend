<?php

namespace App\Action\Job;

use App\Controller\Core\Services;
use App\Controller\Email\EmailTemplateController;
use App\Controller\Job\JobOfferInformationController;
use App\Entity\Email\Email;
use App\Entity\Email\EmailAttachment;
use App\Entity\Email\EmailTemplate;
use App\Entity\Job\JobApplication;
use App\Entity\Job\JobOfferInformation;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Enum\File\UploadedFileSourceEnum;
use App\Repository\Email\EmailRepository;
use App\Repository\File\UploadedFileRepository;
use App\Repository\Job\JobApplicationRepository;
use App\Repository\Job\JobSearchResultRepository;
use App\Response\Base\BaseResponse;
use App\Response\Job\GetJobApplications;
use App\Service\Email\EmailAttachmentService;
use App\Service\Job\JobApplicationService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\System\State\SystemStateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Handles routes calls related to the {@see JobApplication}
 */
class JobApplicationAction
{

    public function __construct(
        private readonly EmailRepository               $emailRepository,
        private readonly JobApplicationRepository      $jobApplicationRepository,
        private readonly Services                      $services,
        private readonly JwtAuthenticationService      $jwtAuthenticationService,
        private readonly EmailTemplateController       $emailTemplateController,
        private readonly EntityManagerInterface        $entityManager,
        private readonly JobOfferInformationController $jobOfferInformationController,
        private readonly UploadedFileRepository        $uploadedFileRepository,
        private readonly EmailAttachmentService        $emailAttachmentService,
        private readonly JobApplicationService         $jobApplicationService,
        private readonly JobSearchResultRepository     $jobSearchResultRepository,
        private readonly TranslatorInterface           $translator,
        private readonly SystemStateService            $systemStateService
    ) {
    }

    /**
     * Handles returning the job applications for given user
     *
     * @return JsonResponse
     */
    #[Route("/job-application/find-all", name: "job_application_find_all", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function findAll(): JsonResponse
    {
        $user                   = $this->services->getJwtAuthenticationService()->getUserFromRequest();
        $applicationMinimumData = $this->jobApplicationRepository->findAllMinimumForUser($user);

        /**
         * Fetching E-Mail this way on purpose. Earlier it was "entity fetch > serialize via symfony/serializer",
         * and then fetching E-Mails was added to make things faster. Yet everything was too slow anyway, thus moved
         * to fetching array of data.
         *
         * This block stays because it just works and is not slowing anything now.
         */
        foreach ($applicationMinimumData as &$data) {
            $data['email'] = $this->emailRepository->findFirstRecipient($data['emailId']);
        }

        $response = GetJobApplications::buildOkResponse();
        $response->setJobApplications($applicationMinimumData);

        return $response->toJsonResponse();
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/job-application/apply", name: "job_application_apply", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function applyToOffer(Request $request): JsonResponse
    {
        $user        = $this->jwtAuthenticationService->getUserFromRequest();
        $isJsonValid = $this->services->getValidationService()->validateJson($request->getContent());
        if (!$isJsonValid) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        if ($this->systemStateService->isSystemDisabled()) {
            return BaseResponse::buildMaintenanceResponse($this->translator->trans('state.disabled.downForMaintenance'))->toJsonResponse();
        }

        $dataArray        = json_decode($request->getContent(), true);
        $filterValues     = $dataArray[JobApplicationInterface::ATTACHED_FILTERS];
        $applicationsData = $dataArray[JobApplicationInterface::APPLICATIONS_DATA];
        $templateId       = $dataArray[JobApplicationInterface::TEMPLATE_ID];
        $attachedFileIds  = $dataArray[JobApplicationInterface::ATTACHED_FILE_IDS];
        $jobSearchId      = $dataArray[JobApplicationInterface::JOB_SEARCH_ID];
        $template         = $this->emailTemplateController->findOneById($templateId);

        if (empty($template)) {
            $this->services->getLoggerService()->critical("No entity of id: {$templateId} was found for class: ". EmailTemplate::class);
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $jobSearch = $this->jobSearchResultRepository->find($jobSearchId);
        if (empty($jobSearch)) {
            $this->services->getLoggerService()->critical("No entity of id: {$jobSearchId} was found for class: ". JobSearchResult::class);
            return BaseResponse::buildNotFoundResponse()->toJsonResponse();
        }

        $this->entityManager->beginTransaction();
        try {
            $validationResult = $this->jobApplicationService->validateRecipientsIntegrity($applicationsData, $filterValues);
            if (!$validationResult->isSuccess()) {
                return BaseResponse::buildBadRequestErrorResponse($validationResult->getMessage())->toJsonResponse();
            }

            $applicationsData = $this->jobApplicationService->excludeApplicationDataOffers($applicationsData);
            if (empty($applicationsData)) {
                $message = $this->services->getTranslator()->trans('email.wizard.sendEmails.save.thereAreNoOffersToApplyFor');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $this->jobApplicationService->buyEmailSending($applicationsData, $jobSearch);
            foreach ($applicationsData as $applicationData) {
                $this->handleSingleApplication($applicationData, $template, $attachedFileIds, $user, $jobSearch);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        }catch(Exception | TypeError $e){
            $this->entityManager->rollback();
            $this->services->getLoggerService()->logException($e, [
                "Could not save all E-Mails - rolling back"
            ]);

            $errorMessage = $this->services->getTranslator()->trans('email.wizard.sendEmails.save.error.couldNotSaveAllEmails');
            return BaseResponse::buildInternalServerErrorResponse($errorMessage)->toJsonResponse();
        }

        $message = $this->services->getTranslator()->trans('email.wizard.sendEmails.save.success');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

    /**
     * Handle job offer information for application
     *
     * @param array $applicationData
     *
     * @return JobOfferInformation
     *
     */
    private function handleJobOfferInformation(array $applicationData): JobOfferInformation
    {
        $offerId          = $applicationData[JobApplicationInterface::OFFER_ID];
        $offerUrl         = $applicationData[JobApplicationInterface::OFFER_URL];
        $offerTitle       = $applicationData[JobApplicationInterface::OFFER_TITLE];
        $offerCompanyName = $applicationData[JobApplicationInterface::OFFER_COMPANY_NAME];

        $jobOfferInformation = $this->jobOfferInformationController->findByExternalId($offerId);
        if (empty($jobOfferInformation)) {
            $jobOfferInformation = new JobOfferInformation();
            $jobOfferInformation->setCompanyName($offerCompanyName);
            $jobOfferInformation->setTitle($offerTitle);
            $jobOfferInformation->setOriginalUrl($offerUrl);
            $jobOfferInformation->setExternalId($offerId);

            $this->entityManager->persist($jobOfferInformation);
        }

        return $jobOfferInformation;
    }

    /**
     * Handles building Email for given application
     *
     * @param array         $applicationData
     * @param EmailTemplate $template
     *
     * @return Email
     */
    private function buildEmailEntity(array $applicationData, EmailTemplate $template): Email
    {
        $user      = $this->jwtAuthenticationService->getUserFromRequest();
        $body      = $applicationData[JobApplicationInterface::EMAIL_BODY];
        $recipient = $applicationData[JobApplicationInterface::RECIPIENT];

        $offerUrl     = $applicationData[JobApplicationInterface::OFFER_URL];
        $offerTitle   = $applicationData[JobApplicationInterface::OFFER_TITLE];
        $jobOfferHost = parse_url($offerUrl, PHP_URL_HOST);

        $emailEntity = new Email();
        $emailEntity->setSender($user);
        $emailEntity->setTemplate($template);

        $subject = $template->getSubject();
        if (!$template->isSubjectSet()) {
            $subject = "[{$this->translator->trans(('email.wizard.subject.fragment.prefix'))}] {$offerTitle} / {$jobOfferHost}";
        }

        $emailEntity->setSubject($subject);
        $emailEntity->setBody($body);
        $emailEntity->setRecipients([$recipient]);

        $this->entityManager->persist($emailEntity);
        $this->entityManager->flush(); // even tho this slows the process it's needed else the email id is not present later on

        return $emailEntity;
    }

    /**
     * Handles creating job application
     *
     * @param JobOfferInformation $jobOfferInformation
     * @param Email $emailEntity
     * @param User $user
     */
    private function createJobApplication(JobOfferInformation $jobOfferInformation, Email $emailEntity, User $user): void
    {
        $jobApplication = new JobApplication();
        $jobApplication->setUser($user);
        $jobApplication->setJobOffer($jobOfferInformation);
        $jobApplication->setEmail($emailEntity);
        $this->entityManager->persist($jobApplication);
    }

    /**
     * Will handle providing email attachments:
     * - at this moment this should only be
     *
     * @param array $attachedFileIds
     * @param User  $user
     * @param Email $email
     *
     * @return EmailAttachment[]
     * @throws Exception
     */
    private function handleEmailAttachments(array $attachedFileIds, User $user, Email $email): array
    {
        $attachments = [];
        foreach ($attachedFileIds as $fileId) {
            $file = $this->uploadedFileRepository->findForUser($fileId, $user, UploadedFileSourceEnum::CV);
            if (empty($file)) {
                $message = "
                    Tried to find uploaded file for user, but nothing could be found. 
                    Maybe front data was manipulated or file was removed by user in other tab.
                    - userId: {$user->getId()},
                    - fileId: {$fileId},
                    - source: CV,
                ";
                throw new LogicException($message);
            }

            $attachment = $this->emailAttachmentService->prepareAttachment(
                $file->getLocalFileName(),
                $file->getPathWithFileName(),
                $email
            );

            $attachment->setRemoveFile(true);

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * @param mixed           $applicationData
     * @param EmailTemplate   $template
     * @param mixed           $attachedFileIds
     * @param User            $user
     * @param JobSearchResult $jobSearch
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function handleSingleApplication(
        mixed           $applicationData,
        EmailTemplate   $template,
        mixed           $attachedFileIds,
        User            $user,
        JobSearchResult $jobSearch
    ): void {
        $jobOfferInformation = $this->handleJobOfferInformation($applicationData);
        $emailEntity         = $this->buildEmailEntity($applicationData, $template);
        $attachedFiles       = $this->handleEmailAttachments($attachedFileIds, $user, $emailEntity);

        $emailEntity->setAttachments($attachedFiles);
        foreach ($attachedFiles as $attachment) {
            $attachment->setEmail($emailEntity);
            $this->entityManager->persist($attachment);
        }

        $jobSearch->addJobOfferInformation($jobOfferInformation);
        $this->createJobApplication($jobOfferInformation, $emailEntity, $user);
    }
}