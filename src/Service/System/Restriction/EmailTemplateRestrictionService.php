<?php

namespace App\Service\System\Restriction;

use App\Entity\Ecommerce\Account\AccountType;
use App\Entity\Security\User;
use App\Service\Security\JwtAuthenticationService;
use LogicException;

/**
 * Controls the restriction related to the email templates
 */
class EmailTemplateRestrictionService
{
    private const MAP_ACCOUNT_TYPE_TO_MAX_TEMPLATES = [
        AccountType::TYPE_FREE                => 10,
        AccountType::TYPE_MEMBERSHIP_STANDARD => 15,
    ];

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Decided that pdf generating can happen ONLY if user buys some points for the first time.
     * Want to avoid case where some ppl will just use the platform to generate their CV
     *
     * @param User|null $user - is a must, because this logic is used also before user is actually authenticated
     *
     * @return bool
     */
    public function canGeneratePdf(?user $user = null): bool
    {
        if (empty($user)) {
            $user = $this->jwtAuthenticationService->getUserFromRequest();
        }

        return !$user->getPointHistory()->isEmpty();
    }

    /**
     * Check if user has reached max allowed E-Mail templates
     *
     * @return bool
     */
    public function hasReachedMaxTemplates(): bool
    {
        $maxAllowedTemplates     = $this->getMaxAllowedTemplates();
        $currentCountOfTemplates = $this->getCountOfTemplates();
        $isMaxTemplatesReached   = ($currentCountOfTemplates >= $maxAllowedTemplates);

        return $isMaxTemplatesReached;
    }

    /**
     * Get number of max allowed E-Mail templates for given user, based on his account type
     *
     * @param User|null $user
     *
     * @return int
     */
    public function getMaxAllowedTemplates(?User $user = null): int
    {
        $usedUser = $user ?? $this->jwtAuthenticationService->getUserFromRequest();

        $max = self::MAP_ACCOUNT_TYPE_TO_MAX_TEMPLATES[$usedUser->getAccount()->getType()->getName()] ?? null;
        if (empty($max)) {
            $msg = "Could not get max allowed E-mail templates for user: {$usedUser->getId()}, with account type: {$usedUser->getAccount()->getType()->getId()}";
            throw new LogicException($msg);
        }

        return $max;
    }

    /**
     * Returns count of templates that user have right now
     *
     * @return int
     */
    public function getCountOfTemplates(): int
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();

        return $user->getActiveEmailTemplates()->count();
    }
}