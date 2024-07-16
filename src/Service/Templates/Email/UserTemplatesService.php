<?php

namespace App\Service\Templates\Email;

use App\Controller\Core\ConfigLoader;
use App\Controller\Security\UserController;
use App\Entity\Security\User;
use App\Service\Routing\FrontendLinkGenerator;
use App\Service\Security\JwtAuthenticationService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Handles twig templates related logic
 */
class UserTemplatesService
{

    const TEMPLATE_PATH_MAIL_SECURITY_USER_LINK_TO_RESET_PASSWORD  = "mail/security/user/request/reset-password.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_SEND_NEW_PASSWORD       = "mail/security/user/feedback/send-new-password.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_ACTIVATION_LINK         = "mail/security/user/request/user-activation.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_HAS_BEEN_REMOVED        = "mail/security/user/feedback/confirmation-user-removed.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_USER_HAS_BEEN_ACTIVATED = "mail/security/user/feedback/confirmation-user-activation.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_HAS_CONFIRMED_REGISTER  = "mail/security/user/action-needed/confirmation-user-registered.twig";
    const TEMPLATE_PATH_MAIL_SECURITY_USER_CONFIRM_EMAIL_CHANGE    = "mail/security/user/action-needed/confirm-email-address-change.twig";

    /**
     * @var Environment $environment
     */
    private Environment $environment;

    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * @param Environment              $environment
     * @param ConfigLoader             $configLoader
     * @param UserController           $userController
     * @param FrontendLinkGenerator    $frontendLinkGenerator
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function __construct(
        Environment                               $environment,
        ConfigLoader                              $configLoader,
        UserController                            $userController,
        private readonly FrontendLinkGenerator    $frontendLinkGenerator,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    )
    {
        $this->userController = $userController;
        $this->configLoader   = $configLoader;
        $this->environment    = $environment;
    }

    /**
     * Will render the template used as mail, contain:
     * - information that password has been set anew,
     * - new password (raw!)
     *
     * @param User $user
     * @param string $planPassword
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderUserSendNewPasswordPasswordEmailTemplate(User $user, string $planPassword): string
    {
        $templateData = [
            'user'         => $user,
            'projectName'  => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'planPassword' => $planPassword,
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_SEND_NEW_PASSWORD, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - link with second link which then will reset the password
     *
     * @param User $user
     * @return string
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderEmailTemplateWithLinkToUserPasswordReset(User $user): string
    {
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'link'        => $this->userController->generateResetPasswordLink($user),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_LINK_TO_RESET_PASSWORD, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - link to user activation,
     *
     * @param User $user
     * @return string
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderEmailTemplateWithUserActivationLink(User $user): string
    {
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'link'        => $this->userController->generateActivateUserLink($user),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_ACTIVATION_LINK, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - information that user has removed his profile
     *
     * @param User $user
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderEmailTemplateUserHasBeenRemoved(User $user): string
    {
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_HAS_BEEN_REMOVED, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - information that user has confirmed registration
     *
     * @param User $user
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws JWTDecodeFailureException
     */
    public function renderEmailTemplateUserRegistered(User $user): string
    {
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'link'        => $this->userController->generateActivateUserLink($user),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_HAS_CONFIRMED_REGISTER, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - information that user has confirmed registration
     *
     * @param string $newEmailAddress
     *
     * @return string
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderEmailAddressChangeConfirmation(string $newEmailAddress): string
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'link'        => $this->frontendLinkGenerator->generateEmailChangeConfirmationLink($user, $newEmailAddress),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_CONFIRM_EMAIL_CHANGE, $templateData);
    }

    /**
     * Will render the template used as mail, contain:
     * - information that user has been activated
     *
     * @param User $user
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderEmailTemplateUserHasBeenActivated(User $user): string
    {
        $templateData = [
            'user'        => $user,
            'projectName' => $this->configLoader->getConfigLoaderProject()->getProjectName(),
        ];

        return $this->environment->render(self::TEMPLATE_PATH_MAIL_SECURITY_USER_USER_HAS_BEEN_ACTIVATED, $templateData);
    }

}