<?php

namespace App\Service\System\Restriction;

use App\Enum\Email\TemplateIdentifierEnum;
use App\Repository\Email\EmailRepository;

/**
 * Ensures that given caller is not abusing the password reset requests as this will generate email sending
 * and some email sending might cause quota exceeding or generating unwanted fees
 */
class PasswordResetRestrictionService
{
    private const MIN_CHECK_OFFSET               = 60;
    private const LOW_THRESHOLD_AMOUNT_OF_EMAILS = 2;

    public function __construct(
        private readonly EmailRepository $emailRepository
    ){}

    /**
     * Check if the calling the link for generating email which contains link for password reset can be called or not.
     * This is based on check how many times was this logic recently called.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isAllowed(string $email): bool
    {
        $emails = $this->emailRepository->getLastEmailsByIdentifier(TemplateIdentifierEnum::REQUEST_PASSWORD_RESET_LINK->name, $email, self::MIN_CHECK_OFFSET);
        $countOfEmails = count($emails);

        if ($countOfEmails >= self::LOW_THRESHOLD_AMOUNT_OF_EMAILS) {
            return false;
        }

        return true;
    }
}