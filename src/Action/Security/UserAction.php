<?php

namespace App\Action\Security;

use App\Attribute\AllowInactiveUser;
use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Env;
use App\Controller\Core\Services;
use App\Controller\Security\AccountController;
use App\Controller\Security\UserController;
use App\Controller\Storage\BannedJwtTokenStorageController;
use App\Controller\Storage\OneTimeJwtTokenStorageController;
use App\DTO\Security\RegisterDataDTO;
use App\Entity\Address\Address;
use App\Entity\Security\User;
use App\Form\Security\RegisterForm;
use App\Response\Base\BaseResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\PasswordGeneratorService;
use App\Service\Security\UserService;
use App\Service\System\Restriction\AccountActivationEmailRequestRestrictionService;
use App\Service\System\Restriction\PasswordResetRestrictionService;
use App\Service\System\Restriction\UserRegisterRestrictionService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles user based actions
 *
 * Class UserAction
 * @package App\Action\Security
 */
class UserAction extends AbstractController
{
    const ROUTE_NAME_LOGIN                        = "app_login";
    const ROUTE_NAME_RESET_PASSWORD               = "rest_password";
    const ROUTE_NAME_REQUEST_PASSWORD_RESET_LINK  = "request_password_rest_link";
    const ROUTE_NAME_REQUEST_USER_ACTIVATION_LINK = "request_user_activation_link";
    const ROUTE_NAME_REQUEST_USER_REMOVAL_LINK    = "request_user_removal_link";
    const ROUT_NAME_REMOVE_USER    = "remove_user";
    const ROUTE_NAME_ACTIVATE_USER = "activate_user";
    const ROUT_NAME_REGISTER_USER  = "register_user";

    const KEY_USER_EMAIL = "email";

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * @var AccountController $accountController
     */
    private AccountController $accountController;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var OneTimeJwtTokenStorageController $oneTimeJwtTokenStorageController
     */
    private OneTimeJwtTokenStorageController $oneTimeJwtTokenStorageController;

    /**
     * @var BannedJwtTokenStorageController $bannedJwtTokenStorageController
     */
    private BannedJwtTokenStorageController $bannedJwtTokenStorageController;

    /**
     * UserAction constructor.
     *
     * @param UserController                                  $userController
     * @param Services                                        $services
     * @param AccountController                               $accountController
     * @param EntityManagerInterface                          $entityManager
     * @param OneTimeJwtTokenStorageController                $oneTimeJwtTokenStorageController
     * @param BannedJwtTokenStorageController                 $bannedJwtTokenStorageController
     * @param UserService                                     $userService
     * @param AccountActivationEmailRequestRestrictionService $accountActivationEmailRequestRestrictionService
     * @param PasswordResetRestrictionService                 $passwordResetRestrictionService
     * @param JwtAuthenticationService                        $jwtAuthenticationService
     * @param TranslatorInterface                             $translator
     * @param PasswordGeneratorService                        $passwordGeneratorService
     * @param UserRegisterRestrictionService                  $userRegisterRestrictionService
     */
    public function __construct(
        UserController                                                   $userController,
        Services                                                         $services,
        AccountController                                                $accountController,
        EntityManagerInterface                                           $entityManager,
        OneTimeJwtTokenStorageController                                 $oneTimeJwtTokenStorageController,
        BannedJwtTokenStorageController                                  $bannedJwtTokenStorageController,
        private readonly UserService                                     $userService,
        private readonly AccountActivationEmailRequestRestrictionService $accountActivationEmailRequestRestrictionService,
        private readonly PasswordResetRestrictionService                 $passwordResetRestrictionService,
        private readonly JwtAuthenticationService                        $jwtAuthenticationService,
        private readonly TranslatorInterface                             $translator,
        private readonly PasswordGeneratorService                        $passwordGeneratorService,
        private readonly UserRegisterRestrictionService                  $userRegisterRestrictionService
    )
    {
        $this->bannedJwtTokenStorageController  = $bannedJwtTokenStorageController;
        $this->oneTimeJwtTokenStorageController = $oneTimeJwtTokenStorageController;
        $this->entityManager                    = $entityManager;
        $this->accountController                = $accountController;
        $this->userController                   = $userController;
        $this->services                         = $services;
    }

    /**
     * This route is used by the {@see LexikJWTAuthenticationBundle}
     *
     * @return JsonResponse
     */
    #[Route("/login", name: self::ROUTE_NAME_LOGIN)]
    #[JwtAuthenticationDisabledAttribute]
    public function login(): JsonResponse
    {
        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles user removal
     *
     * @return JsonResponse
     */
    #[Route("/remove-user/", name: self::ROUT_NAME_REMOVE_USER, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function removeUser(): JsonResponse
    {

        try{
            $user = $this->services->getJwtAuthenticationService()->getUserFromRequest();
            if (empty($user)) {
                $this->services->getLoggerService()->info("There is no user currently logged in");
                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            if (Env::isDemo()) {
                return BaseResponse::buildAccessDeniedResponse($this->translator->trans('generic.demo.disabled'))->toJsonResponse();
            }

            $isRemoved = $this->userController->softDeleteUser($user);
            if(!$isRemoved){
                $this->services->getLoggerService()->critical("Could not remove the user!", [
                    "userId" => $user->getId(),
                ]);
                return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
            }

            $this->userService->saveRemoveUserEmail($user);
            $this->bannedJwtTokenStorageController->saveJwtTokenAsBannedTokenForRequest();
        }catch(Exception $e){
            $this->services->getLoggerService()->logException($e);
            return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles resetting password
     *
     * @param string $token
     * @return JsonResponse
     */
    #[Route("/reset-password/{token}", name: self::ROUTE_NAME_RESET_PASSWORD, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function resetPassword(string $token): JsonResponse
    {
        if (!$this->jwtAuthenticationService->isAnyGrantedToUser([User::RIGHT_USER_RESET_PASSWORD], $token)) {
            $msg = $this->translator->trans('security.login.messages.noRightToResetPassword');
            return BaseResponse::buildAccessDeniedResponse($msg)->toJsonResponse();
        }

        if (Env::isDemo()) {
            return BaseResponse::buildAccessDeniedResponse($this->translator->trans('generic.demo.disabled'))->toJsonResponse();
        }

        try{
            if( $this->oneTimeJwtTokenStorageController->isOneTimeTokenAlreadyUsed($token) ){
                $message = $this->services->getTranslator()->trans('security.jwt.thisTokenHasBeenAlreadyUsed');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if( $this->services->getJwtAuthenticationService()->isTokenExpired($token) ){
                $message = $this->services->getTranslator()->trans('user.resetPassword.thisLinkHasExpiredPleaseRequestPasswordResetAgain');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $user = $this->services->getJwtAuthenticationService()->getUserForToken($token, false);
            if( empty($user) ){
                $this->services->getLoggerService()->info("No user was found for token, probably already removed", [
                    "token" => $token,
                ]);

                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            $rawCustomPassword = $this->services->getPasswordGeneratorService()->generateCustomPassword();
            $encryptedPassword = $this->services->getUserSecurityService()->encodeRawPasswordForUserEntity($rawCustomPassword);

            $user->setPassword($encryptedPassword);
            $this->userController->save($user);

            $this->userService->saveNewPasswordEmail($user, $rawCustomPassword);
            $this->oneTimeJwtTokenStorageController->setTokenExpired($token);
        }catch(Exception $e){

            if( JwtAuthenticationService::isJwtTokenException($e) ){
                $this->services->getLoggerService()->warning("Provided jwt token is not valid", [
                    "authenticationMessage" => $e->getMessage(),
                    "token"                 => $token,
                ]);

                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }else{

                $this->services->getLoggerService()->logException($e);
                return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
            }
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles request for sending password reset link
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/request-password-reset-link", name: self::ROUTE_NAME_REQUEST_PASSWORD_RESET_LINK, methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function requestPasswordResetLink(Request $request): JsonResponse
    {
        if (Env::isDemo()) {
            return BaseResponse::buildAccessDeniedResponse($this->translator->trans('generic.demo.disabled'))->toJsonResponse();
        }

        try{
            $requestJson = $request->getContent();
            $dataArray   = json_decode($requestJson, true);

            $isJsonValid = $this->services->getValidationService()->validateJson($requestJson);
            if(!$isJsonValid){
                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            if( !array_key_exists(self::KEY_USER_EMAIL, $dataArray) ){
                $message = $this->services->getTranslator()->trans('security.login.messages.badRequest');
                $this->services->getLoggerService()->info("Request is missing key: " . self::KEY_USER_EMAIL);
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $email        = $dataArray[self::KEY_USER_EMAIL];
            $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
            if(!$isEmailValid){
                $message = $this->services->getTranslator()->trans('mails.security.user.requestPasswordResetLink.response.invalidEmail');
                $this->services->getLoggerService()->info("Provided E-mail is not valid: " . $email);
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $user = $this->userController->getOneByEmail($email);
            if( empty($user) ){
                $message = $this->services->getTranslator()->trans('mails.security.user.requestPasswordResetLink.response.noUserFound');
                $this->services->getLoggerService()->info("No user was found with such E-mail: " . $email);
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $isAllowed = $this->passwordResetRestrictionService->isAllowed($email);
            if (!$isAllowed) {
                $message = $this->translator->trans('user.requestPasswordResetEmail.toManyTimes');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if ($user->isDeleted()) {
                $message = $this->translator->trans('user.requestPasswordResetEmail.userDeleted');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $this->userService->savePasswordResetEmail($user);
        }catch(Exception $e){
            $this->services->getLoggerService()->logException($e);
            return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles request for sending user activation link
     *
     * No need to check for email validity, if user is active etc,
     * because only place where this link is/should be called is after being logged in
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/request-user-activation-link", name: self::ROUTE_NAME_REQUEST_USER_ACTIVATION_LINK, methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    #[AllowInactiveUser] // that's very special case, the user is logged in for just a brief moment when requesting the link
    public function requestUserActivationLink(Request $request): JsonResponse
    {
        try {
            $requestJson = $request->getContent();
            $isJsonValid = $this->services->getValidationService()->validateJson($requestJson);
            if (!$isJsonValid) {
                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            $user    = $this->jwtAuthenticationService->getUserFromRequest();
            $canCall = $this->accountActivationEmailRequestRestrictionService->isAllowed($user->getEmail());
            if (!$canCall) {
                $message = $this->translator->trans('user.requestAccountActivationEmail.toManyTimes');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if ($user->isActive()) {
                $message = $this->translator->trans('user.requestAccountActivationEmail.alreadyActivated');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $this->userService->saveAccountActivationEmail();
        } catch (Exception $e) {
            $this->services->getLoggerService()->logException($e);
            return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles activating user
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    #[Route("/activate-user/{token}", name: self::ROUTE_NAME_ACTIVATE_USER, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function activateUser(string $token): JsonResponse
    {
        if (!$this->jwtAuthenticationService->isAnyGrantedToUser([User::RIGHT_USER_ACTIVATE_ACCOUNT], $token)) {
            $msg = $this->translator->trans('security.login.messages.noRightToActivate');
            return BaseResponse::buildAccessDeniedResponse($msg)->toJsonResponse();
        }

        if( $this->oneTimeJwtTokenStorageController->isOneTimeTokenAlreadyUsed($token) ){
            $message = $this->services->getTranslator()->trans('security.jwt.thisTokenHasBeenAlreadyUsed');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        $user = $this->services->getJwtAuthenticationService()->getUserForToken($token, true);

        try{

            if( $user->isActive() ){
                $message = $this->services->getTranslator()->trans('security.login.messages.thisUserHasAlreadyBeenActivated');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $isTokenExpired = $this->services->getJwtAuthenticationService()->isTokenExpired($token);
            if ($isTokenExpired) {
                $this->userService->saveAccountActivationEmail();

                $message = $this->services->getTranslator()->trans('mails.security.user.userActivationLink.response.expiredToken');
                return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            if( empty($user) ){
                $this->services->getLoggerService()->info("No user was found for token, probably already removed", [
                    "token" => $token,
                ]);

                return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
            }

            $user->removeRole(User::RIGHT_USER_ACTIVATE_ACCOUNT);
            $user->setActive(true);
            $this->userController->save($user);

            $this->userService->saveAccountHasBeenActivatedEmail($user);
            $this->oneTimeJwtTokenStorageController->setTokenExpired($token);
        }catch(Exception $e){
            $this->services->getLoggerService()->logException($e);
            return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
        }

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Handles registering user
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/register-user", name: self::ROUT_NAME_REGISTER_USER, methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function registerUser(Request $request): JsonResponse
    {
        $this->entityManager->beginTransaction();
        {
            try{
                if ($this->userRegisterRestrictionService->isExcessiveCall()) {
                    $response = BaseResponse::buildBadRequestErrorResponse();
                    $response->setMessage($this->translator->trans("user.register.toManyRegistrationsFromIp"));
                    return $response->toJsonResponse();
                }

                $form = $this->createForm(RegisterForm::class);
                $this->services->getFormService()->handlePostFormForAxiosCall($form, $request);

                /** @var RegisterDataDTO $registerDto */
                $registerDto  = $form->getData();

                $violatedFields = $this->services->getValidationService()->validateAndReturnArrayOfInvalidFieldsWithMessages($registerDto);
                if( !$form->isValid() || !$violatedFields->isSuccess() ){
                    $this->entityManager->rollback();
                    $response = BaseResponse::buildInvalidFieldsRequestErrorResponse();
                    $response->setInvalidFields($violatedFields->getViolationsWithMessages());
                    $response->setMessage($this->translator->trans('generic.someFieldsAreInvalid'));

                    return $response->toJsonResponse();
                }

                $userForEmail = $this->userController->getOneByEmail($registerDto->getEmail());
                if (!empty($userForEmail)) {
                    $this->entityManager->rollback();
                    $message = $this->services->getTranslator()->trans('mails.security.user.register.violations.emailIsAlreadyInUse');
                    if ($userForEmail->isDeleted()) {
                        $message = $this->services->getTranslator()->trans('mails.security.user.register.message.emailCannotBeUsed');
                    }

                    return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
                }

                if (!$this->passwordGeneratorService->validatePassword($registerDto->getPassword())) {
                    $message = $this->services->getTranslator()->trans('security.password.message.toWeak');
                    return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
                }

                $hashedPassword = $this->services->getUserSecurityService()->encodeRawPasswordForUserEntity($registerDto->getPassword());
                $user           = User::buildFromRegisterDto($registerDto, $hashedPassword);
                $account        = $this->accountController->buildAccountOfFreeType($user);
                $address        = Address::buildFromRegisterDto($registerDto);

                $user->setAddress($address);
                $user->setAccount($account);

                $this->entityManager->persist($address);
                $this->entityManager->flush();
                $this->userController->save($user);
                $this->accountController->saveAccount($account);
                $this->userService->saveUserRegisteredEmail($user);
            }catch(Exception $e){
                $this->entityManager->rollback();
                $this->services->getLoggerService()->logException($e);
                return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
            }

        }
        $this->entityManager->commit();
        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}