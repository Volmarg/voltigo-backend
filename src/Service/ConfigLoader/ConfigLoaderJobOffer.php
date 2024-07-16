<?php

namespace App\Service\ConfigLoader;

/**
 * Contains job offers related configuration
 */
class ConfigLoaderJobOffer
{

    public function __construct(
        private int $applicationDaysPeriodSameOffer
    ) {
    }

    /**
     * @return int
     */
    public function getApplicationDaysPeriodSameOffer(): int
    {
        return $this->applicationDaysPeriodSameOffer;
    }

}