<?php

namespace App\Service\Storage;

use App\Constants\RabbitMq\Common\CommunicationConstants;
use App\Entity\Security\User;
use App\Entity\Storage\AmqpStorage;
use App\Service\Security\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Service for {@see AmqpStorage}
 */
class AmqpStorageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    /**
     * Will create {@see AmqpStorage} from {@see AMQPMessage}
     *
     * @param string      $messageBody
     * @param string      $targetClass
     * @param bool        $isResponseExpected
     * @param string|null $uniqueId
     * @param int|null    $userId                 - can be provided because the userId will be available in the message
     *                                            only when "consumed", otherwise id has to be provided manually
     *                                            Not using the {@see UserService} on purpose because the NOW logged-in user
     *                                            might be different from the one in "consumed message", don't want to
     *                                            accidentally append one when it's not desired
     *
     * @return AmqpStorage
     */
    public function createFromAmqpMessage(string $messageBody, string $targetClass, bool $isResponseExpected, ?string $uniqueId = null, ?int $userId = null): AmqpStorage
    {
        $usedUserId = $this->extractUserId($messageBody) ?? $userId;
        $user       = null;
        if (!empty($usedUserId)) {
            $user = $this->entityManager->getRepository(User::class)->getOneById($usedUserId);
        }

        $storageEntry = new AmqpStorage();
        $storageEntry->setMessage($messageBody);
        $storageEntry->setUser($user);
        $storageEntry->setTargetClass($targetClass);
        $storageEntry->setExpectResponse($isResponseExpected);

        if (!empty($uniqueId)) {
            $storageEntry->setUniqueId($uniqueId);
        }

        return $storageEntry;
    }

    /**
     * Will return the user id extracted from the message, or null if none was found
     *
     * @param string $messageBody
     *
     * @return int|null
     */
    private function extractUserId(string $messageBody): ?int
    {
        $dataArray = json_decode($messageBody, true);
        return $dataArray[CommunicationConstants::KEY_USER_ID] ?? null;
    }

}