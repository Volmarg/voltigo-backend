<?php

namespace App\Service\Cleanup;

use App\Entity\Storage\OneTimeJwtTokenStorage;
use App\Repository\Storage\OneTimeJwtTokenStorageRepository;

/**
 * Handles cleaning the {@see OneTimeJwtTokenStorage}
 */
class OneTimeJwtTokenStorageCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly OneTimeJwtTokenStorageRepository $oneTimeJwtTokenStorageRepository
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->oneTimeJwtTokenStorageRepository->removeOldEntries();
    }
}