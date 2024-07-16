<?php

namespace App\Service\System\Restriction;

use App\Controller\Core\Env;
use App\Entity\Ecommerce\Account\AccountType;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Service\Security\JwtAuthenticationService;
use LogicException;

/**
 * Controls the restriction related to the job searching
 */
class JobSearchRestrictionService
{
    private const MAP_ACCOUNT_TYPE_TO_MAX_ACTIVE_SEARCH = [
        AccountType::TYPE_FREE                => 1,
        AccountType::TYPE_MEMBERSHIP_STANDARD => 2,
    ];

    public const MAP_ACCOUNT_TYPE_TO_MAX_SEARCHED_KEYWORDS = [
        AccountType::TYPE_FREE                => 1,
        AccountType::TYPE_MEMBERSHIP_STANDARD => 2,
    ];

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Check if user is allowed to search offers
     * @return bool
     */
    public function hasReachedMaxActiveSearch(): bool
    {
        $maxAllowedSearchesCount  = $this->getMaxActiveSearch();
        $activeSearchesCount      = $this->getCountOfActive();
        $isMaxActiveSearchReached = ($activeSearchesCount >= $maxAllowedSearchesCount);

        return $isMaxActiveSearchReached;
    }

    /**
     * Get number of max allowed parallel active job searches for given user, based on his account type
     *
     * @param User|null $user
     *
     * @return int
     */
    public function getMaxActiveSearch(?User $user = null): int
    {
        // that's important because this method is used also for initial jwt creation, so user can be delivered from it
        $usedUser = $user ?? $this->jwtAuthenticationService->getUserFromRequest();

        $max  = self::MAP_ACCOUNT_TYPE_TO_MAX_ACTIVE_SEARCH[$usedUser->getAccount()->getType()->getName()] ?? null;
        if (empty($max)) {
            throw new LogicException("Could not get max allowed active search for user: {$usedUser->getId()}, with account type: {$usedUser->getAccount()->getType()->getId()}");
        }

        return $max;
    }

    /**
     * Get number of max allowed keywords used in single search
     * - each service used in search will look for offers with that keyword, so the more keywords there are, the
     *   longer it all takes and the higher the costs for paid services might be
     *
     * @param User|null $user
     *
     * @return int
     */
    public function getMaxSearchedKeywords(?User $user = null): int
    {
        // that's important because this method is used also for initial jwt creation, so user can be delivered from it
        $usedUser = $user ?? $this->jwtAuthenticationService->getUserFromRequest();

        $max  = self::MAP_ACCOUNT_TYPE_TO_MAX_SEARCHED_KEYWORDS[$usedUser->getAccount()->getType()->getName()] ?? null;
        if (empty($max)) {
            throw new LogicException("Couldn't get max allowed keywords per search for user: {$usedUser->getId()}, with account type: {$usedUser->getAccount()->getType()->getId()}");
        }

        return $max;
    }

    /**
     * Returns count of currently active searches running
     *
     * @return int
     */
    public function getCountOfActive(): int
    {
        $user                   = $this->jwtAuthenticationService->getUserFromRequest();
        $activeSearchesCount    = $user->getJobSearchResults()->filter(function(JobSearchResult $jobSearchResult){
            return $jobSearchResult->isRunning() || $jobSearchResult->isPending();
        })->count();

        return $activeSearchesCount;
    }

    /**
     * Check if job search functionality is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return Env::isJobSearchDisabled();
    }
}