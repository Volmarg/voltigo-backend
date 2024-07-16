<?php

namespace App\DTO\RabbitMq\Consumer\JobSearch\Done;

use JobSearcherBridge\Enum\JobOfferExtraction\StatusEnum;
use App\RabbitMq\Consumer\JobSearch\JobSearchDoneConsumer;

/**
 * For {@see JobSearchDoneConsumer}
 */
class ParameterBag
{

    public function __construct(
        private readonly bool $success,
        private readonly ?int $extractionId,
        private readonly int $searchId,
        private readonly ?StatusEnum $extractionStatus,
        private readonly int $percentageDone
    ) {
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return int|null
     */
    public function getExtractionId(): ?int
    {
        return $this->extractionId;
    }

    /**
     * @return int
     */
    public function getSearchId(): int
    {
        return $this->searchId;
    }

    /**
     * @return StatusEnum|null
     */
    public function getExtractionStatus(): ?StatusEnum
    {
        return $this->extractionStatus;
    }

    /**
     * @return int
     */
    public function getPercentageDone(): int
    {
        return $this->percentageDone;
    }

}