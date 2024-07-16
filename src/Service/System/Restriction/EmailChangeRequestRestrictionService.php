<?php

namespace App\Service\System\Restriction;

use App\Enum\Email\TemplateIdentifierEnum;
use App\Repository\Email\EmailRepository;
use App\Repository\Security\UserRepository;

/**
 * Ensures that the email request is not triggered to often
 */
class EmailChangeRequestRestrictionService
{
    private const MIN_CHECK_OFFSET               = 60; // 1h
    private const LOW_THRESHOLD_AMOUNT_OF_EMAILS = 2;

    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly UserRepository $userRepository
    ){}

    /**
     * Check if user is allowed to request the email change.
     * This is based on check how many times was this logic recently called.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isAllowed(string $email): bool
    {
        $emails = $this->emailRepository->getLastEmailsByIdentifier(
            TemplateIdentifierEnum::EMAIL_ADDRESS_CHANGE_CONFIRMATION->name,
            $email,
            self::MIN_CHECK_OFFSET,
            true
        );
        $countOfEmails = count($emails);

        if ($countOfEmails >= self::LOW_THRESHOLD_AMOUNT_OF_EMAILS) {
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isUsed(string $email): bool
    {
        $user = $this->userRepository->getOneByEmail($email);
        return !empty($user);
    }

}