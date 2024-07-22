<?php

namespace App\Action\User\Setting;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Env;
use App\Controller\Storage\OneTimeJwtTokenStorageController;
use App\DTO\Internal\User\Setting\PersonalDataDTO;
use App\Entity\Address\Address;
use App\Enum\Address\CountryEnum;
use App\Enum\File\UploadedFileSourceEnum;
use App\Enum\Service\Serialization\SerializerType;
use App\Repository\File\UploadedFileRepository;
use App\Response\Base\BaseResponse;
use App\Service\Logger\LoggerService;
use App\Service\Routing\FrontendLinkGenerator;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\UserService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\System\Restriction\EmailChangeRequestRestrictionService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Handles user settings base data actions
 */
class BaseDataAction extends AbstractController
{
    public const ROUTE_NAME_EMAIL_CHANGE = "user.base_data.email.change";

    public function __construct(
        private readonly ObjectSerializerService              $objectSerializerService,
        private readonly ValidationService                    $validationService,
        private readonly TranslatorInterface                  $translator,
        private readonly EntityManagerInterface               $entityManager,
        private readonly JwtAuthenticationService             $jwtAuthenticationService,
        private readonly UserService                          $userService,
        private readonly EmailChangeRequestRestrictionService $emailChangeRequestRestrictionService,
        private readonly OneTimeJwtTokenStorageController     $oneTimeJwtTokenStorageController,
        private readonly LoggerService                        $logger,
        private readonly UploadedFileRepository               $uploadedFileRepository
    ){}

    /**
     * Saves the user personal data
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/user/base-data/personal-data/save", name: "user.base_data.personal_data.save", methods: [Request::METHOD_OPTIONS, Request::METHOD_POST])]
    public function savePersonalData(Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validationService->validateJson($json)) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        /** @var PersonalDataDTO $personalData */
        $personalData     = $this->objectSerializerService->fromJson($json, PersonalDataDTO::class, SerializerType::CUSTOM);
        $validationResult = $this->validationService->validateAndReturnArrayOfInvalidFieldsWithMessages($personalData);

        if (!$validationResult->isSuccess()) {
            return BaseResponse::buildInvalidFieldsRequestErrorResponse($validationResult->getViolationsWithMessages())->toJsonResponse();
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        $user->setFirstName($personalData->getFirstName());
        $user->setLastName($personalData->getLastName());

        $address = $user->getAddress();
        if (empty($address)) {
            $address = new Address();
        }

        $country = null;
        if (!empty($personalData->getCountry())) {
            $country = CountryEnum::tryFrom(strtoupper($personalData->getCountry()));
        }

        $address->setCity($personalData->getCity());
        $address->setZip($personalData->getZip());
        $address->setStreet($personalData->getStreet());
        $address->setHomeNumber($personalData->getHomeNumber());
        $address->setCountry($country);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $message = $this->translator->trans('user.settings.base_data.save.message.ok');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

    /**
     * Sets the "change mail" into to queue for sending
     *
     * @param string $emailAddress
     *
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route("/user/base-data/email/request-change/{emailAddress}", name: "user.base_data.email.request_change", methods: [Request::METHOD_OPTIONS, Request::METHOD_GET])]
    public function requestEmailChange(string $emailAddress): JsonResponse
    {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $message = $this->translator->trans('user.settings.base_data.email.message.incorrectSyntax');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        if (Env::isDemo()) {
            return BaseResponse::buildBadRequestErrorResponse($this->translator->trans('generic.demo.disabled'))->toJsonResponse();
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if ($user->getEmail() === $emailAddress) {
            $message = $this->translator->trans('user.settings.base_data.email.message.sameEmailAddress');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        $isAllowed = $this->emailChangeRequestRestrictionService->isAllowed($user->getEmail());
        if (!$isAllowed) {
            $message = $this->translator->trans('user.settings.base_data.email.message.requestedToManyTimes');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        if ($this->emailChangeRequestRestrictionService->isUsed($emailAddress)) {
            $message = $this->translator->trans('user.settings.base_data.email.message.emailAlreadyTaken');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        $this->userService->saveEmailChangeConfirmationMail($emailAddress, $user);

        $message = $this->translator->trans('user.settings.base_data.email.message.emailWillSoonBeSent');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

    /**
     * Changes the user profile image to the one that was recently uploaded.
     * This route is called directly after the file upload.
     *
     * It handles:
     * - removing uploaded files of type {@see UploadedFileSourceEnum::PROFILE_IMAGE} but only leaving the latest one
     * - shall call to this route fail, then it's not so bad because only latest image is taken as profile picture
     *
     * So the change of profile image is basically based on cleaning up files when there are more than 1 for profile image
     *
     * @return JsonResponse
     */
    #[Route("/user/base-data/profile-image/change", name: "user.base_data.profile_image.change", methods: [Request::METHOD_OPTIONS, Request::METHOD_GET])]
    public function changeProfileImage(): JsonResponse
    {
        $user             = $this->jwtAuthenticationService->getUserFromRequest();
        $allProfileImages = $this->uploadedFileRepository->findForUserBySource($user, UploadedFileSourceEnum::PROFILE_IMAGE);
        $latestFileIndex  = null;
        $latestFile       = null;

        // should never happen but if it did then whatever it's fine like that
        if (empty($allProfileImages)) {
            return BaseResponse::buildOkResponse()->toJsonResponse();
        }

        foreach ($allProfileImages as $index => $profileImage) {
            if (empty($latestFileIndex)) {
                $latestFileIndex = $index;
                $latestFile      = $profileImage;
                continue;
            }

            if ($latestFile->getCreated()->getTimestamp() < $profileImage->getCreated()->getTimestamp()) {
                $latestFileIndex = $index;
                $latestFile      = $profileImage;
            }
        }

        unset($allProfileImages[$latestFileIndex]);
        foreach ($allProfileImages as $profileImage) {
            try {
                $profileImage->removeFromServer();
            } catch (Exception $e) {
                $this->logger->logException($e);
                // nothing else, try next file

                continue;
            }

            $this->entityManager->remove($profileImage);;
        }

        $this->entityManager->flush();

        // ok response is desired, for user it should not matter as he will get always the latest profile image shown anyway
        $message = $this->translator->trans('user.settings.base_data.profileImage.message.ok');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

    /**
     * Changes the user email
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    #[Route("/user/base-data/email/change/{token}", name: self::ROUTE_NAME_EMAIL_CHANGE, methods: [Request::METHOD_OPTIONS, Request::METHOD_GET])]
    #[JwtAuthenticationDisabledAttribute]
    public function changeEmail(string $token): JsonResponse
    {
        try {
            if ($this->oneTimeJwtTokenStorageController->isOneTimeTokenAlreadyUsed($token)) {
                $message = $this->translator->trans('security.jwt.thisTokenHasBeenAlreadyUsed');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if ($this->jwtAuthenticationService->isTokenExpired($token)) {
                $message = $this->translator->trans('user.resetPassword.thisLinkHasExpiredPleaseRequestPasswordResetAgain');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $user = $this->jwtAuthenticationService->getUserForToken($token, false);
            if (
                    empty($user)
                ||  !$user->isActive()
                ||  $user->isDeleted()
            ) {
                $this->logger->info("No user was found for token - probably already removed, or user is inactive / deleted", [
                    "token" => $token,
                ]);

                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            $payload  = $this->jwtAuthenticationService->getPayloadFromToken($token);
            $newEmail = $payload[FrontendLinkGenerator::PAYLOAD_KEY_NEW_EMAIL_ADDRESS] ?? null;
            if (empty($newEmail)) {
                $message = $this->translator->trans('user.settings.base_data.email.message.incorrectSyntax');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if ($newEmail === $user->getEmail()) {
                $message = $this->translator->trans('user.settings.base_data.email.message.sameEmailAddress');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $user->setEmail($newEmail);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->oneTimeJwtTokenStorageController->setTokenExpired($token);
        } catch (Exception $e) {

            if (JwtAuthenticationService::isJwtTokenException($e)) {
                $this->logger->warning("Provided jwt token is not valid", [
                    "authenticationMessage" => $e->getMessage(),
                    "token"                 => $token,
                ]);

                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            } else {

                $this->logger->logException($e);
                return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
            }
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

}
