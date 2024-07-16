<?php

namespace App\Service\Cleanup;

use App\Entity\Storage\BannedJwtTokenStorage;
use App\Repository\Storage\BannedJwtTokenStorageRepository;
use Doctrine\DBAL\Exception;

/**
 * Handles cleaning the {@see BannedJwtTokenStorage}
 */
class BannedJwtTokenStorageCleanupService implements CleanupServiceInterface
{

    public function __construct(
        private readonly BannedJwtTokenStorageRepository $bannedJwtTokenStorageRepository
    ){}

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function cleanUp(): int
    {
        return $this->bannedJwtTokenStorageRepository->removeOldEntries();
    }
}