<?php

namespace App\Service\Cleanup;

use App\Entity\Storage\ApiStorage;
use App\Repository\Storage\ApiStorageRepository;

/**
 * Handles cleaning the {@see ApiStorage}
 */
class ApiStorageCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly ApiStorageRepository $apiStorageRepository
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return 0;
    }
}