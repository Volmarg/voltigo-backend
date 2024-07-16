<?php

namespace App\RabbitMq\Consumer;

use App\Entity\Storage\AmqpStorage;
use App\Service\RabbitMq\AmqpService;
use App\Service\Storage\AmqpStorageService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Provides some fixes / pre-configuration for the {@see Consumer}
 */
abstract class BaseConsumer implements ConsumerInterface
{
    private const MESSAGE_DELAY = 10; // seconds

    public function __construct(
        private readonly ValidationService      $validationService,
        private readonly AmqpStorageService     $amqpStorageService,
        private readonly EntityManagerInterface $entityManager,
        private readonly AmqpService            $amqpService,
        private readonly LoggerInterface        $aqmpLogger,
    ) {}

    /**
     * Handles the execution of the consumer code
     *
     * @param AMQPMessage $msg
     * @param AmqpStorage $amqpStorageEntity
     *
     * @return int
     */
    public abstract function doExecute(AMQPMessage $msg, AmqpStorage $amqpStorageEntity): int;

    /**
     * Used in {@see AmqpStorage::setExpectResponse()}
     *
     * @return bool
     */
    protected abstract function isResponseExpected(): bool;

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function execute(AMQPMessage $msg): int
    {
        $this->entityManager->beginTransaction();

        try {
            $storageEntity = $this->beforeExecute($msg);
            if (empty($storageEntity)) {
                $this->aqmpLogger->critical("Could not create storage entity for message, rejecting it and re-queueing", [
                    "message" => $msg,
                ]);

                sleep(self::MESSAGE_DELAY);
                return ConsumerInterface::MSG_REJECT_REQUEUE;
            }

            $responseCode = $this->doExecute($msg, $storageEntity);
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            $this->aqmpLogger->critical("Exception was thrown in base consumer - rolling back", [
                "exception" => [
                    "class"   => $e::class,
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
                "info" => [
                    "what-now" => "Re-queueing the rabbit message",
                    "message"  => $msg->getBody()
                ]
            ]);

            sleep(self::MESSAGE_DELAY);
            return ConsumerInterface::MSG_REJECT_REQUEUE;
        }

        return $responseCode;
    }

    /**
     * If everything is ok then {@see AmqpStorage} entity is returned, null otherwise
     *
     * @param AMQPMessage $msg
     *
     * @return AmqpStorage|null
     * @throws Exception
     */
    private function beforeExecute(AMQPMessage $msg): ?AmqpStorage
    {
        if(!$this->validationService->validateJson($msg->getBody())){
            return null;
        }

        $storageEntity = $this->amqpStorageService->createFromAmqpMessage($msg->getBody(), static::class, $this->isResponseExpected());
        $this->handleRelatedStorageEntry($msg->getBody(),$storageEntity);

        $this->entityManager->persist($storageEntity);;
        $this->entityManager->flush();

        return $storageEntity;
    }

    /**
     * Will handle setting related storage entity, meaning such scenario:
     * - Project A sends message (a - with its own tracking id) to project B,
     * - Project B responds with message (b - with its own tracking id, AND the tracking id of received message (a))
     *
     * So with this the db entry of message (a) can be bound to message (b) for which new entry in DB is created too
     *
     * @param string      $messageBody
     * @param AmqpStorage $amqpStorage
     *
     * @return void
     * @throws Exception
     */
    private function handleRelatedStorageEntry(string $messageBody, AmqpStorage $amqpStorage): void
    {
        $incomingMessageId = $this->amqpService->extractOriginalMessageId($messageBody);
        $relatedEntry = null;
        if (!empty($incomingMessageId)) {
            $relatedEntry = $this->entityManager->getRepository(AmqpStorage::class)->findByUniqueId($incomingMessageId);
        }

        $amqpStorage->setRelatedStorageEntry($relatedEntry);
    }

}