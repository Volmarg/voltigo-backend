<?php

namespace App\Service\Cleanup;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\CsrfTokenStorage;
use App\Repository\Storage\CsrfTokenStorageRepository;

/**
 * Handles cleaning the {@see CsrfTokenStorage}
 */
class CsrfTokenStorageCleanupService implements CleanupServiceInterface
{

    public function __construct(
        private readonly CsrfTokenStorageRepository $csrfTokenStorageRepository,
        private readonly ConfigLoader $configLoader
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->csrfTokenStorageRepository->removeOlderThanHours($this->configLoader->getStorageConfigLoader()->getCrfTokenStorageLifetimeHours());
    }
}