<?php

namespace App\Service\System\Restriction;

use App\Controller\Core\Env;
use App\Enum\Email\TemplateIdentifierEnum;
use App\Repository\Email\EmailRepository;
use App\Service\Security\JwtAuthenticationService;
use DateTime;

/**
 * Ensures that nobody will abuse the: "send template test E-Mail" on E-Mail Templates page
 */
class EmailTemplateTestSendingRestrictionService
{
    public const MAX_PER_DAY = 30;

    public function __construct(
        private readonly EmailRepository          $emailRepository,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ) {
    }

    /**
     * Returns the count of E-Mails sent today
     *
     * @return int
     */
    public function countSentToday(): int
    {
        if (Env::isDemo()) {
            return 0;
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();

        // this one is calculated dynamically because check has to be always made toward 00:00:00 of current day
        $now    = new DateTime();
        $target = new DateTime("{$now->format('Y-m-d')} 00:00:00");

        $minOffset    = abs($now->diff($target)->i);
        $hoursOffset  = abs($now->diff($target)->h);
        $allMinOffset = ($hoursOffset * 60) + $minOffset;

        $emails = $this->emailRepository->getLastEmailsByIdentifier(TemplateIdentifierEnum::TEMPLATE_TEST_EMAIL->name, $user->getEmail(), $allMinOffset);

        return count($emails);
    }

    /**
     * Check if user is allowed to send any more test E-Mails
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        if (Env::isDemo()) {
            return true;
        }

        $todayCount = $this->countSentToday();
        if ($todayCount >= self::MAX_PER_DAY) {
            return false;
        }

        return true;
    }

}