<?php

namespace App\Service\Cleanup;

use App\Entity\Job\JobSearchResult;
use App\Repository\Job\JobOfferInformationRepository;

/**
 * Handles cleaning the {@see JobSearchResult}
 */
class JobInformationCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly JobOfferInformationRepository $jobOfferInformationRepository,
        private readonly int                           $offerInformationMaxLifetimeHours
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->jobOfferInformationRepository->removeOlderThanHours($this->offerInformationMaxLifetimeHours);
    }
}
