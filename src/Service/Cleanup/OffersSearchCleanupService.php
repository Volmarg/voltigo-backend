<?php

namespace App\Service\Cleanup;

use App\Entity\Job\JobSearchResult;
use App\Repository\Job\JobSearchResultRepository;

/**
 * Handles cleaning the {@see JobSearchResult}
 */
class OffersSearchCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly JobSearchResultRepository $jobSearchResultRepository,
        private readonly int                       $offersMaxLifetimeHours
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->jobSearchResultRepository->removeOlderThanHours($this->offersMaxLifetimeHours);
    }
}
