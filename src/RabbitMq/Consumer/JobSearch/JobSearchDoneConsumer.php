<?php

namespace App\RabbitMq\Consumer\JobSearch;

use App\Constants\RabbitMq\Common\CommonConstants;
use App\Constants\RabbitMq\Consumer\JobSearch\JobSearchDoneConsumerConstants;
use App\DTO\RabbitMq\Consumer\JobSearch\Done\ParameterBag;
use App\DTO\RabbitMq\Producer\JobSearch\Start\ParameterBag as JobSearchProducerParameterBag;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Entity\Storage\AmqpStorage;
use App\Service\Job\JobSearchDoneService;
use App\Service\Serialization\ObjectSerializerService;
use Exception;
use JobSearcherBridge\Enum\JobOfferExtraction\StatusEnum;
use App\RabbitMq\Consumer\BaseConsumer;
use App\Service\RabbitMq\AmqpService;
use App\Service\Storage\AmqpStorageService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * Handler for AMQP call indicating that job offers search has been done (finished)
 */
class JobSearchDoneConsumer extends BaseConsumer
{
    public function __construct(
        private readonly ObjectSerializerService      $objectSerializerService,
        private readonly LoggerInterface              $amqpLogger,
        private readonly ValidationService            $validationService,
        private readonly EntityManagerInterface       $entityManager,
        private readonly JobSearchDoneService         $jobSearchDoneService,
        AmqpStorageService                            $amqpStorageService,
        AmqpService                                   $amqpService
    ) {
        parent::__construct($this->validationService, $amqpStorageService, $this->entityManager, $amqpService, $this->amqpLogger);
    }

    /**
     * {@inheritDoc}
     *
     * @param AMQPMessage $msg
     * @param AmqpStorage $amqpStorageEntity
     *
     * @return int
     *
     * @throws Exception
     */
    public function doExecute(AMQPMessage $msg, AmqpStorage $amqpStorageEntity): int
    {
        $parameterBag = $this->buildParameterBag($msg);
        $user         = $amqpStorageEntity->getUser() ?? $amqpStorageEntity?->getRelatedStorageEntry()?->getUser();

        if (empty($user)) {
            $message = "
                Job search done must be related to user, yet none is present - 
                both for incoming message and related entry. Storage id: {$amqpStorageEntity->getId()}.
            ";
            throw new LogicException($message);
        }

        $this->updateAndSendNotifications($user, $parameterBag, $amqpStorageEntity);

        return ConsumerInterface::MSG_ACK;
    }

    /**
     * {@inheritDoc};
     */
    protected function isResponseExpected(): bool
    {
        return false;
    }

    /**
     * Build the bag of parameters from the {@see AMQPMessage} these are then later required for handling the consumption
     * @param AMQPMessage $msg
     *
     * @return ParameterBag
     */
    private function buildParameterBag(AMQPMessage $msg): ParameterBag
    {
        $json = $msg->getBody();
        $this->validationService->validateJson($json);

        $dataArray        = json_decode($json, true);
        $success          = $dataArray[CommonConstants::KEY_SUCCESS]                          ?? null;
        $extractionId     = $dataArray[JobSearchDoneConsumerConstants::KEY_EXTRACTION_ID]     ?? null;
        $searchId         = $dataArray[JobSearchDoneConsumerConstants::KEY_SEARCH_ID]         ?? null;
        $extractionStatus = $dataArray[JobSearchDoneConsumerConstants::KEY_EXTRACTION_STATUS] ?? null;
        $percentageDone   = $dataArray[JobSearchDoneConsumerConstants::KEY_PERCENTAGE_DONE  ] ?? null;

        if (is_null($success)) {
            throw new LogicException("Success information is missing for job search done message. Content: {$msg->getBody()}");
        }

        if (is_null($searchId)) {
            throw new LogicException("Search id information is missing for job search done message. Content: {$msg->getBody()}");
        }

        // because something might have crashed before the extraction was set
        if ($success && is_null($extractionId)) {
            throw new LogicException("Extraction id information is missing for job search done message. Content: {$msg->getBody()}");
        }

        if (empty($extractionStatus)) {
            throw new LogicException("Extraction status information is missing for job search done message. Content: {$msg->getBody()}");
        }

        if (empty($percentageDone) && $percentageDone != 0) {
            throw new LogicException("Percentage done is missing for job search done message. Content: {$msg->getBody()}");
        }

        $parameterBag = new ParameterBag($success, $extractionId, $searchId, StatusEnum::tryFrom($extractionStatus), $percentageDone);

        return $parameterBag;
    }

    /**
     * Handles sending:
     * - message on the user,
     * - message on admin (if something failed),
     * - websocket based notification on user,
     *
     * @param User         $user
     * @param ParameterBag $parameterBag
     * @param AmqpStorage  $amqpStorage
     *
     * @throws Exception
     */
    private function updateAndSendNotifications(User $user, ParameterBag $parameterBag, AmqpStorage $amqpStorage): void
    {
        $jobSearchStorageProducerEntry = $amqpStorage->getRelatedStorageEntry();
        if (empty($jobSearchStorageProducerEntry)) {
            $this->amqpLogger->critical("No related storage entry was found for: " . self::class, [
                "info"            => "This is logically not possible, bug?",
                "consumedMessage" => $amqpStorage->getMessage(),
            ]);
            return;
        }

        /** @var $jobSearchProducerParamBag JobSearchProducerParameterBag */
        $jobSearchProducerParamBag = $this->objectSerializerService->fromJson($jobSearchStorageProducerEntry->getMessage(), JobSearchProducerParameterBag::class);
        $searchResult = $this->entityManager->find(JobSearchResult::class, $jobSearchProducerParamBag->getSearchId());

        if (is_null($parameterBag->getExtractionStatus())) {
            throw new LogicException("Status is missing for search: {$searchResult->getId()}");
        }

        $statusFromSearcher = $searchResult->getStatusFromOfferHandlerState($parameterBag->getExtractionStatus());
        if ($statusFromSearcher === $searchResult->getStatus()) {
            $this->amqpLogger->critical("This search result has already the same status set! Skipping!", [
                "amqpStorageId" => $amqpStorage->getId(),
                "searchId"      => $searchResult->getId(),
            ]);

            return;
        }

        $this->jobSearchDoneService->updateSearchEntity($parameterBag, $searchResult);
        $this->jobSearchDoneService->buildEmail($parameterBag, $searchResult);

        if (!$parameterBag->isSuccess() || empty($parameterBag->getExtractionId())) {
            $this->amqpLogger->critical("Job search failed, check the log on the job searching tool", [
                "storageId" => $amqpStorage->getId(),
            ]);
        }

        $this->jobSearchDoneService->notifyViaSocket($user, $parameterBag);
    }

}