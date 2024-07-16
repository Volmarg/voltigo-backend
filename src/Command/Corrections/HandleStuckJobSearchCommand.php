<?php

namespace App\Command\Corrections;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\DTO\RabbitMq\Consumer\JobSearch\Done\ParameterBag;
use App\Entity\Job\JobSearchResult;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Repository\Job\JobSearchResultRepository;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Job\JobSearchDoneService;
use App\Service\Logger\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcherBridge\Enum\JobOfferExtraction\StatusEnum;
use JobSearcherBridge\Response\Extraction\GetExtractionsMinimalDataResponse;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

class HandleStuckJobSearchCommand extends AbstractCommand
{
    public const COMMAND_NAME = "corrections:handle-stuck-job-search";
    private const MIN_STUCK_HOURS = 1;

    private array $searchIdsWithoutData = [];
    private readonly array $searchResultFinishedStatuses;
    private readonly array $jobSearcherSuccessStatuses;

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Goes over job searches, and fetches the data from JobSearcher in case search is stuck for too long");
    }

    public function __construct(
        private readonly JobSearchResultRepository $jobSearchResultRepository,
        private readonly JobSearchService          $jobSearchService,
        private readonly LoggerService             $loggerService,
        private readonly JobSearchDoneService      $jobSearchDoneService,
        private readonly EntityManagerInterface    $entityManager,
        ConfigLoader                               $configLoader,
        KernelInterface                            $kernel
    )
    {
        $this->jobSearcherSuccessStatuses = [
            StatusEnum::STATUS_IMPORTED->value,
            StatusEnum::STATUS_PARTIALLY_IMPORTED->value,
        ];

        parent::__construct($configLoader, $kernel);
    }

    /**
     * Execute command logic
     *
     * @return int
     *
     * @throws GuzzleException
     */
    protected function executeLogic(): int
    {
        try {
            $pending = $this->jobSearchResultRepository->findAllOlderThanWithStatus(SearchResultStatusEnum::PENDING->name, self::MIN_STUCK_HOURS);
            $wip     = $this->jobSearchResultRepository->findAllOlderThanWithStatus(SearchResultStatusEnum::WIP->name, self::MIN_STUCK_HOURS);
            $allStuckSearches = [
                ...$pending,
                ...$wip,
            ];

            if (empty($allStuckSearches)) {
                $this->io->success("Nothing to handle");
                return self::SUCCESS;
            }

            $msg = count($allStuckSearches) . " searches are stuck even tho these should be handled by rabbit! Did rabbit failed somewhere in between, or current run is REALLY long?";
            $this->logger->critical($msg);
            $this->io->warning($msg);

            $stuckIds = array_map(fn(JobSearchResult $searchResult) => $searchResult->getId(), $allStuckSearches);
            $response = $this->jobSearchService->getExtractionsMinimalData([], $stuckIds);
            $this->handleResponse($response, $stuckIds);
        } catch (Exception|TypeError $e) {
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Will go over data delivered for each stuck id and handle it
     *
     * @param GetExtractionsMinimalDataResponse $response
     * @param array                             $searchIds
     */
    private function handleResponse(GetExtractionsMinimalDataResponse $response, array $searchIds): void
    {
        foreach ($searchIds as $searchId) {
            try {
                $searchEntity = $this->jobSearchResultRepository->find($searchId);
                if (is_null($searchEntity)) {
                    throw new Exception("No job search entity was found for id: {$searchId}");
                }

                $statuses       = $response->getClientIdStatuses();
                $percentageDone = $response->getClientIdPercentageDone();
                $extractionIds  = $response->getClientIdExtractionId();

                if (!$this->validateDataDelivery($searchId, $statuses, $percentageDone, $extractionIds)) {
                    continue;
                }

                $status         = $statuses[$searchId];
                $percentageDone = $percentageDone[$searchId];
                $extractionId   = $extractionIds[$searchId];

                $statusEnum = StatusEnum::tryFrom($status);
                if (is_null($statusEnum)) {
                    throw new LogicException("Could not build status enum, tried from string: {$statusEnum}");
                }

                if ($this->shouldSkipStatus($searchEntity, $statusEnum)) {
                    continue;
                }

                $isSuccess    = in_array($statusEnum->value, $this->jobSearcherSuccessStatuses);
                $parameterBag = new ParameterBag($isSuccess, $extractionId, $searchId, $statusEnum, $percentageDone);

                $this->entityManager->beginTransaction();
                $this->jobSearchDoneService->updateSearchEntity($parameterBag, $searchEntity);
                $this->jobSearchDoneService->buildEmail($parameterBag, $searchEntity);
                $this->entityManager->commit();

                $this->jobSearchDoneService->notifyViaSocket($searchEntity->getUser(), $parameterBag);
            } catch (Exception|TypeError $e) {
                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }

                $this->loggerService->logException($e, [
                    "stuckSearchId" => $searchId,
                ]);
                $this->io->error("Could not handle response for search id: {$searchId}. Error: {$e->getMessage()}");
            }
        }

        $this->handleSearchIdsWithoutData();
    }

    /**
     * Logs / writes IO error if some ids are missing data
     */
    private function handleSearchIdsWithoutData(): void
    {
        if (!empty($this->searchIdsWithoutData)) {
            $msg = "Some of the stuck ids are missing data (part or all of it). Ids: " . json_encode($this->searchIdsWithoutData);
            $this->io->error($msg);
            $this->logger->critical($msg);
        }
    }

    /**
     * Check if all data is present for each of search ids
     *
     * @param mixed $searchId
     * @param array $statuses
     * @param array $percentageDone
     * @param array $extractionIds
     *
     * @return bool - true if all is OK, false otherwise
     */
    private function validateDataDelivery(mixed $searchId, array $statuses, array $percentageDone, array $extractionIds): bool
    {
        if (
                !array_key_exists($searchId, $statuses)
            ||  !array_key_exists($searchId, $percentageDone)
            ||  !array_key_exists($searchId, $extractionIds)
        ) {
            $this->searchIdsWithoutData[] = $searchId;
            return false;
        }

        return true;
    }

    /**
     * @param JobSearchResult $searchEntity
     * @param StatusEnum      $statusEnum
     *
     * @return bool
     */
    private function shouldSkipStatus(JobSearchResult $searchEntity, StatusEnum $statusEnum): bool
    {
        if (!in_array($searchEntity->getStatusFromOfferHandlerState($statusEnum), $this->searchResultFinishedStatuses)) {
            $msg = "Offer searcher returned not-finished status: {$statusEnum->name} for search: {$searchEntity->getId()}";
            $this->logger->warning($msg);
            $this->io->warning($msg);
            return true;
        }
        return false;
    }

}