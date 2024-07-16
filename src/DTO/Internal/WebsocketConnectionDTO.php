<?php

namespace App\DTO\Internal;

use App\Service\Libs\Ratchet\RatchetConnectionDecorator;
use DateTime;

/**
 * Base representation of the websocket connection
 */
class WebsocketConnectionDTO
{
    /**
     * @var RatchetConnectionDecorator $client
     */
    private RatchetConnectionDecorator $client;

    /**
     * @var string $connectionIdentifier
     */
    private string $connectionIdentifier;

    /**
     * @var string|null $userId
     */
    private ?string $userId;

    /**
     * @var DateTime $connectionOpenDateTime
     */
    private DateTime $connectionOpenDateTime;

    public function __construct()
    {
        $this->connectionOpenDateTime = new DateTime();
    }

    /**
     * @return RatchetConnectionDecorator
     */
    public function getClient(): RatchetConnectionDecorator
    {
        return $this->client;
    }

    /**
     * @param RatchetConnectionDecorator $client
     */
    public function setClient(RatchetConnectionDecorator $client): void
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    /**
     * @param string $connectionIdentifier
     */
    public function setConnectionIdentifier(string $connectionIdentifier): void
    {
        $this->connectionIdentifier = $connectionIdentifier;
    }

    /**
     * @return null|string
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @param null|string $userId
     */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return DateTime
     */
    public function getConnectionOpenDateTime(): DateTime
    {
        return $this->connectionOpenDateTime;
    }

    /**
     * @param DateTime $connectionOpenDateTime
     */
    public function setConnectionOpenDateTime(DateTime $connectionOpenDateTime): void
    {
        $this->connectionOpenDateTime = $connectionOpenDateTime;
    }

    /**
     * Will return the identifier stamp
     * @return string
     */
    public function getIdentifierStamp(): string
    {
        return "[{$this->getConnectionIdentifier()}] ";
    }

}