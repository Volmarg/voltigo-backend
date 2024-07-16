<?php

namespace App\DTO\RabbitMq\Common;

/**
 * Provides logic / props shared between producer and consumer
 */
class ProducerConsumerBaseDto
{
    /**
     * @var int|null $userId
     */
    private ?int $userId = null;

    /**
     * @var string $uniqueId
     */
    private string $uniqueId;

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     */
    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

}