<?php

namespace App\Service\ConfigLoader;

/**
 * Contain config that is related to the storage entities
 */
class StorageConfigLoader
{
    /**
     * @var int $crfTokenStorageLifetimeHours
     */
    private int $crfTokenStorageLifetimeHours;

    /**
     * @var int $frontendErrorStorageLifetimeHours
     */
    private int $frontendErrorStorageLifetimeHours;

    /**
     * @var int $pageTrackingStorageLifetimeHours
     */
    private int $pageTrackingStorageLifetimeHours;

    /**
     * @var int $amqpTimeJwtTokenStorageLifetimeHours
     */
    private int $amqpTimeJwtTokenStorageLifetimeHours;

    /**
     * @return int
     */
    public function getCrfTokenStorageLifetimeHours(): int
    {
        return $this->crfTokenStorageLifetimeHours;
    }

    /**
     * @param int $crfTokenStorageLifetimeHours
     */
    public function setCrfTokenStorageLifetimeHours(int $crfTokenStorageLifetimeHours): void
    {
        $this->crfTokenStorageLifetimeHours = $crfTokenStorageLifetimeHours;
    }

    /**
     * @return int
     */
    public function getFrontendErrorStorageLifetimeHours(): int
    {
        return $this->frontendErrorStorageLifetimeHours;
    }

    /**
     * @param int $frontendErrorStorageLifetimeHours
     */
    public function setFrontendErrorStorageLifetimeHours(int $frontendErrorStorageLifetimeHours): void
    {
        $this->frontendErrorStorageLifetimeHours = $frontendErrorStorageLifetimeHours;
    }

    /**
     * @return int
     */
    public function getPageTrackingStorageLifetimeHours(): int
    {
        return $this->pageTrackingStorageLifetimeHours;
    }

    /**
     * @param int $pageTrackingStorageLifetimeHours
     */
    public function setPageTrackingStorageLifetimeHours(int $pageTrackingStorageLifetimeHours): void
    {
        $this->pageTrackingStorageLifetimeHours = $pageTrackingStorageLifetimeHours;
    }

    /**
     * @param int $amqpTimeJwtTokenStorageLifetimeHours
     */
    public function setAmqpTimeJwtTokenStorageLifetimeHours(int $amqpTimeJwtTokenStorageLifetimeHours): void
    {
        $this->amqpTimeJwtTokenStorageLifetimeHours = $amqpTimeJwtTokenStorageLifetimeHours;
    }

    /**
     * @return int
     */
    public function getAmqpTimeJwtTokenStorageLifetimeHours(): int
    {
        return $this->amqpTimeJwtTokenStorageLifetimeHours;
    }

}