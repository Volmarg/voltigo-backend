<?php

namespace App\Service\Cleanup;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\FrontendErrorStorage;
use App\Repository\Storage\FrontendErrorStorageRepository;

/**
 * Handles cleaning the {@see FrontendErrorStorage}
 */
class FrontendErrorStorageCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly FrontendErrorStorageRepository $frontendErrorStorageRepository,
        private readonly ConfigLoader $configLoader
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->frontendErrorStorageRepository->removeOlderThanHours($this->configLoader->getStorageConfigLoader()->getFrontendErrorStorageLifetimeHours());
    }
}