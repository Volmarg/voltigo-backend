<?php

namespace App\Command\Monitoring;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Repository\Job\JobSearchResultRepository;
use App\Service\Api\JobSearcher\JobSearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * In general the logic of {@see JobSearchService} handles sending information if extraction is done, and
 * that data is further used in this project to update the extraction entities.
 *
 * It could however happen (in worst case) that the job search / extraction would not get updated on this project
 * side, so this command is used to find such cases and report them.
 */
class LongRunningJobSearchCommand extends AbstractCommand
{
    const COMMAND_NAME = "monitoring:long-running-extractions";

    /**
     * Any search running longer than this is considered stuck and will get reported
     */
    private const MIN_RUN_TIME_HOURS = 3;

    /**
     * {@inheritDoc}
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Checks if there are some long running extractions and reports it");
    }

    /**
     * @param ConfigLoader              $configLoader
     * @param JobSearchResultRepository $jobSearchResultRepository
     * @param KernelInterface           $kernel
     */
    public function __construct(
        protected ConfigLoader                     $configLoader,
        private readonly JobSearchResultRepository $jobSearchResultRepository,
        private readonly KernelInterface           $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * {@inheritDoc}
     */
    protected function executeLogic(): int
    {
        $entities = $this->jobSearchResultRepository->findAllOlderThanWithStatus(
            SearchResultStatusEnum::PENDING->name,
            self::MIN_RUN_TIME_HOURS
        );

        $reportData = [];
        foreach ($entities as $entity) {
            $reportData[] = [
                "entityId"                  => $entity->getId(),
                "createdAt"                 => $entity->getCreated()?->format('Y-m-d H:is'),
                "modifiedAt"                => $entity->getModified()?->format('Y-m-d H:is'),
                "currentMinRunTimeToReport" => self::MIN_RUN_TIME_HOURS,
            ];
        }

        if (empty($reportData)) {
            return Command::SUCCESS;
        }

        $this->logger->critical("Found some long running job searches!", [
            "count" => count($reportData),
            "data"  => $reportData,
        ]);

        return Command::SUCCESS;
    }

}
