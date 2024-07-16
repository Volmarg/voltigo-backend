<?php

namespace App\Service\System\Restriction;

use App\Enum\Email\TemplateIdentifierEnum;
use App\Repository\Email\EmailRepository;

/**
 * Ensures that some genius is not trying to spam the account activation request too much, or someone
 * is just stuck on activation process, not getting E-Mails and is just trying to get through
 */
class AccountActivationEmailRequestRestrictionService
{

    private const MIN_CHECK_OFFSET               = 60;
    private const LOW_THRESHOLD_AMOUNT_OF_EMAILS = 5;

    public function __construct(
        private readonly EmailRepository $emailRepository
    ){}

    /**
     * Check if user is allowed to request the email for account activation.
     * This is based on check how many times was this logic recently called.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isAllowed(string $email): bool
    {
        $emails = $this->emailRepository->getLastEmailsByIdentifier(TemplateIdentifierEnum::ACCOUNT_ACTIVATION->name, $email, self::MIN_CHECK_OFFSET);
        $countOfEmails = count($emails);

        if ($countOfEmails >= self::LOW_THRESHOLD_AMOUNT_OF_EMAILS) {
            return false;
        }

        return true;
    }

}