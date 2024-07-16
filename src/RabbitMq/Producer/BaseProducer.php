<?php

namespace App\RabbitMq\Producer;

use App\Constants\RabbitMq\Common\CommunicationConstants;
use App\Entity\Storage\AmqpStorage;
use App\Enum\RabbitMq\ConnectionTypeEnum;
use App\RabbitMq\Connection\QueueConnectionNames;
use App\Service\Security\UserService;
use App\Service\Storage\AmqpStorageService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;

/**
 * Provides some fixes / pre-configuration for the {@see Producer}
 * Not setting anything in DI as it starts breaking the producer internal logic - that's too much of an issue
 *
 */
abstract class BaseProducer extends Producer
{
    /**
     * @return string
     */
    public abstract function getTargetQueueName(): string;

    /**
     * Used in {@see AmqpStorage::setExpectResponse()}
     *
     * @return bool
     */
    protected abstract function isResponseExpected(): bool;

    /**
     * There is some issue with `name` & `type` keys missing in the producing process
     * which is the package issue itself, so this fixes it,
     *
     * Atm. it's unknown what the keys really are for
     */
    public function fixExchangeOptions(): void
    {
        if (empty($this->getTargetQueueName())) {
            throw new LogicException("Target queue name is missing!");
        }

        QueueConnectionNames::isQueueSupported($this->getTargetQueueName());

        $this->exchangeOptions['name'] = $this->getTargetQueueName();
        $this->exchangeOptions['type'] = ConnectionTypeEnum::DIRECT->value;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param UserService            $userService
     * @param AmqpStorageService     $amqpStorageService
     * @param ValidationService      $validationService
     * @param LoggerInterface        $aqmpLogger
     * @param AbstractConnection     $conn
     * @param AMQPChannel|null       $ch
     * @param null                   $consumerTag
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly AmqpStorageService     $amqpStorageService,
        private readonly ValidationService      $validationService,
        private readonly LoggerInterface        $aqmpLogger,
        AbstractConnection                      $conn,
        AMQPChannel                             $ch = null,
                                                $consumerTag = null,
    )
    {
        $this->fixExchangeOptions();
        parent::__construct($conn, $ch, $consumerTag);
    }

    /**
     * {@inheritDoc}
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = [], array $headers = null)
    {
        if (empty($routingKey)) {
            throw new LogicException("Even tho th routing key is allowed to be null, THIS project requires it to be set!");
        }

        $uniqueId        = uniqid();
        $modifiedMessage = $this->appendBaseKeys($uniqueId, $msgBody);

        $this->aqmpLogger->debug("Publishing message", [
            "class"      => self::class,
            "message"    => $modifiedMessage,
            "routingKey" => $routingKey,
            "properties" => $additionalProperties,
            "headers"    => $headers,
        ]);

        $this->handleStorageEntry($modifiedMessage, $uniqueId);

        parent::publish($modifiedMessage, $routingKey, $additionalProperties, $headers);
    }

    /**
     * Will attach some base keys to each "produce" call:
     *
     * @param string $uniqueId
     * @param string $message
     *
     * @return string
     */
    private function appendBaseKeys(string $uniqueId, string $message): string
    {
        $this->validationService->validateJson($message);

        $dataArray = json_decode($message, true);
        $dataArray[CommunicationConstants::KEY_USER_ID]   = $this->userService->getLoggedInUserId();
        $dataArray[CommunicationConstants::KEY_UNIQUE_ID] = $uniqueId;

        return json_encode($dataArray);
    }

    /**
     * Will handle the saving the AQMP entry in db
     *
     * @param string $messageBody
     * @param string $uniqueId
     */
    private function handleStorageEntry(string $messageBody, string $uniqueId): void
    {
        $storageEntity = $this->amqpStorageService->createFromAmqpMessage(
            $messageBody,
            static::class,
            $this->isResponseExpected(),
            $uniqueId,
        );

        $this->entityManager->persist($storageEntity);
        $this->entityManager->flush();
    }

}