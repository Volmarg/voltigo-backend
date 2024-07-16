<?php

namespace App\Service\Cleanup;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\AmqpStorage;
use App\Repository\Storage\AmqpStorageRepository;

/**
 * Handles cleaning the {@see AmqpStorage}
 */
class AmqpStorageCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly AmqpStorageRepository $amqpStorageRepository,
        private readonly ConfigLoader $configLoader
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        return $this->amqpStorageRepository->removeOlderThanHours($this->configLoader->getStorageConfigLoader()->getAmqpTimeJwtTokenStorageLifetimeHours());
    }
}