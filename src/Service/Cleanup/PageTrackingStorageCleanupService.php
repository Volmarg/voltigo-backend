<?php

namespace App\Service\Cleanup;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\PageTrackingStorage;
use App\Repository\Storage\PageTrackingStorageRepository;

/**
 * Handles cleaning the {@see PageTrackingStorage}
 */
class PageTrackingStorageCleanupService implements CleanupServiceInterface
{

    public function __construct(
        private readonly PageTrackingStorageRepository $pageTrackingStorageRepository,
        private readonly ConfigLoader $configLoader
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        $hoursMaxLifetime = $this->configLoader->getStorageConfigLoader()->getPageTrackingStorageLifetimeHours();
        return $this->pageTrackingStorageRepository->removeOlderThanHours($hoursMaxLifetime);
    }
}