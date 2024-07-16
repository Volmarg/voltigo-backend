<?php

namespace App\Service\Security;

use App\Entity\Email\Email;
use App\Entity\Security\User;
use App\Enum\Email\TemplateIdentifierEnum;
use App\Security\LexitBundleJwtTokenAuthenticator;
use App\Service\Templates\Email\UserTemplatesService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Service for handling user related logic
 */
class UserService
{

    public function __construct(
        private readonly TokenStorageInterface    $tokenStorage,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly UserTemplatesService     $userTemplatesService,
        private readonly TranslatorInterface      $translator,
        private readonly EntityManagerInterface   $entityManager
    ) {}

    /**
     * Will return id of currently logged-in user, or null if non is logged in
     *
     * This method is needed as it's not so simple to find out the logged-in user id for this project.
     * The thing is that:
     * - symfony has its internal state of tracking the user session,
     * - THIS project uses {@see LexitBundleJwtTokenAuthenticator} and it's not sure if it stores the user in symfony session,
     *
     * Thus, 2 ways of trying to obtain the user id, one for standard symfony built-in mechanism, second from jwt token
     *
     * @return int|null
     */
    public function getLoggedInUserId(): ?int
    {
        /** @var User|null $user */
        $user             = $this->tokenStorage->getToken()?->getUser();
        $tokenBasedUserId = $user?->getId();
        $jwtBasedUserId   = $this->jwtAuthenticationService->getUserFromRequest()?->getId();
        $usedUserId       = $tokenBasedUserId ?? $jwtBasedUserId;

        return $usedUserId;
    }

    /**
     * Saves the user account activation email for sending later
     *
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveAccountActivationEmail(): void
    {
        $user    = $this->jwtAuthenticationService->getUserFromRequest();
        $body    = $this->userTemplatesService->renderEmailTemplateWithUserActivationLink($user);
        $subject = $this->translator->trans('mails.security.user.userActivationLink.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::ACCOUNT_ACTIVATION->name);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email that contains link for resetting the password
     *
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function savePasswordResetEmail(User $user): void
    {
        $body    = $this->userTemplatesService->renderEmailTemplateWithLinkToUserPasswordReset($user);
        $subject = $this->translator->trans('mails.security.user.requestPasswordResetLink.subject.passwordResetLink');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::REQUEST_PASSWORD_RESET_LINK->name);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email that contains link for confirming the email address change
     *
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveEmailChangeConfirmationMail(string $newEmailAddress, User $user): void
    {
        $body    = $this->userTemplatesService->renderEmailAddressChangeConfirmation($newEmailAddress);
        $subject = $this->translator->trans('mails.security.user.emailChangeConfirmation.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$newEmailAddress]);
        $email->setIdentifier(TemplateIdentifierEnum::EMAIL_ADDRESS_CHANGE_CONFIRMATION->name);
        $email->setSender($user);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email that contains link for removing the user account
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveRemoveUserEmail(User $user): void
    {
        $body    = $this->userTemplatesService->renderEmailTemplateUserHasBeenRemoved($user);
        $subject = $this->translator->trans('mails.security.user.userRemovalConfirmation.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::CONFIRM_ACCOUNT_REMOVAL->name);
        $email->setSender($user);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email that contains new user new password
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveNewPasswordEmail(User $user, string $rawCustomPassword): void
    {
        $body    = $this->userTemplatesService->renderUserSendNewPasswordPasswordEmailTemplate($user, $rawCustomPassword);
        $subject = $this->translator->trans('mails.security.user.sendNewPassword.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::NEW_PASSWORD_AFTER_REQUEST_CONFIRMATION->name);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email that contains link for account removal
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveAccountHasBeenActivatedEmail(User $user): void
    {
        $body    = $this->userTemplatesService->renderEmailTemplateUserHasBeenActivated($user);
        $subject = $this->translator->trans('mails.security.user.userActivationConfirmation.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::ACCOUNT_HAS_BEEN_ACTIVATED->name);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

    /**
     * Saves the email sent after user registers
     *
     * @param User $user
     *
     * @throws JWTDecodeFailureException
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveUserRegisteredEmail(User $user): void
    {
        $body    = $this->userTemplatesService->renderEmailTemplateUserRegistered($user);
        $subject = $this->translator->trans('mails.security.user.userConfirmationProfileRegistered.subject');

        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setRecipients([$user->getEmail()]);
        $email->setIdentifier(TemplateIdentifierEnum::AFTER_REGISTER_WELCOME->name);

        $this->entityManager->persist($email);
        $this->entityManager->flush();
    }

}