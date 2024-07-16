<?php

namespace App\Service\ConfigLoader;

/**
 * Configuration related to web sockets
 */
class ConfigLoaderWebSocket
{

    /**
     * @var int $nonUserBasedConnectionLifetimeMinutes
     */
    private int $nonUserBasedConnectionLifetimeMinutes;

    /**
     * @var int $userBasedConnectionLifetimeMinutes
     */
    private int $userBasedConnectionLifetimeMinutes;

    /**
     * @return int
     */
    public function getNonUserBasedConnectionLifetimeMinutes(): int
    {
        return $this->nonUserBasedConnectionLifetimeMinutes;
    }

    /**
     * @param int $nonUserBasedConnectionLifetimeMinutes
     */
    public function setNonUserBasedConnectionLifetimeMinutes(int $nonUserBasedConnectionLifetimeMinutes): void
    {
        $this->nonUserBasedConnectionLifetimeMinutes = $nonUserBasedConnectionLifetimeMinutes;
    }

    /**
     * @return int
     */
    public function getUserBasedConnectionLifetimeMinutes(): int
    {
        return $this->userBasedConnectionLifetimeMinutes;
    }

    /**
     * @param int $userBasedConnectionLifetimeMinutes
     */
    public function setUserBasedConnectionLifetimeMinutes(int $userBasedConnectionLifetimeMinutes): void
    {
        $this->userBasedConnectionLifetimeMinutes = $userBasedConnectionLifetimeMinutes;
    }

}