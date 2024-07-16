<?php

namespace App\Command\Corrections;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Job\JobSearchResult;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Enum\Points\UserPointHistoryTypeEnum;
use App\Repository\Job\JobSearchResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

class ReturnPointsForSearchResultCommand extends AbstractCommand
{
    const COMMAND_NAME = "corrections:points:return:search-result";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("
            Goes over search results, checks if there is any below 100% percentage done with partial success.
            If there is such then returns points to the user. Amount of points corresponds missing percentage.
        ");
    }

    public function __construct(
        private readonly JobSearchResultRepository $jobSearchResultRepository,
        private readonly EntityManagerInterface    $entityManager,
        ConfigLoader                               $configLoader,
        KernelInterface                            $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * Execute command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        try {
            $handledEntries = $this->jobSearchResultRepository->findAllRefundAble();
            $countOfEntries = count($handledEntries);
            if (!$countOfEntries) {
                $this->io->note("There are not entries to handle");
                return self::SUCCESS;
            }

            $reFoundData = [];
            $this->io->note("Got {$countOfEntries} record/s to handle");
            foreach ($handledEntries as $entry) {
                $returnedPoints = $this->getReturnedPointsByStatus($entry);
                if (!$returnedPoints) {
                    $this->io->warning("Got some wrong search result entry (id: {$entry->getId()}). Returned points amount is: {$returnedPoints} - skipping");
                    continue;
                }

                $this->grantPoints($returnedPoints, $entry);
                $reFoundData[] = "[{$entry->getStatus()}] User: {$entry->getUser()->getId()}, got {$returnedPoints} point/s back, for search: {$entry->getId()}";
            }

            $this->io->listing($reFoundData);
        } catch (Exception|TypeError $e) {
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Calculate how many points should be returned to user for "DONE" statuses
     *
     * @param JobSearchResult $searchResult
     *
     * @return int
     */
    private function calculateReturnedPointsForDone(JobSearchResult $searchResult): int
    {
        if ($searchResult->getPercentageDone() >= 100) {
            return 0;
        }

        $points         = $searchResult->getUserPointHistory()->getAbsAmountDiff();
        $failPercentage = 100 - $searchResult->getPercentageDone();
        $returnedPoints = $points * ($failPercentage / 100);

        return (int)$returnedPoints;
    }

    /**
     * Will calculate how many points should be returned for error status
     *
     * @param JobSearchResult $entry
     *
     * @return int
     */
    private function calculateReturnedPointsForError(JobSearchResult $entry): int
    {
        if ((int)floor($entry->getPercentageDone()) !== 0) {

            $logMessage = "
                Got search result with error status finished in: {$entry->getPercentageDone()}%.
                There is currently no logic to determine any offset of points returned, whenever it's an error
                it just returns all the points that user spent. Such cases as this one with percentage != 0 
                could indicate that some smaller fixes might be needed on searcher side.
                
                Maybe the error could be changed to low-percentage DONE status, where only missing % based points are returned.
            ";
            $logMessageTrimmed = preg_replace('!\s+!', ' ', $logMessage);

            $this->logger->critical($logMessageTrimmed, [
                "searchId"     => $entry->getId(),
                "extractionId" => $entry->getExternalExtractionId(),
            ]);
        }

        // just return 100% of the points spent
        return $entry->getUserPointHistory()->getAbsAmountDiff();
    }

    /**
     * Grant points to user:
     * - add points to his wallet,
     * - save history entry,
     *
     * @param int             $pointsAmount
     * @param JobSearchResult $searchResult
     */
    private function grantPoints(int $pointsAmount, JobSearchResult $searchResult): void
    {
        $user = $searchResult->getUser();
        $this->entityManager->beginTransaction();
        try {
            $pointsHistory = new UserPointHistory();
            $pointsHistory->setType(UserPointHistoryTypeEnum::RECEIVED->name);
            $pointsHistory->setUser($user);
            $pointsHistory->setAmountBefore($user->getPointsAmount());
            $pointsHistory->setInformation("Job search was only partially successful. Returning points for failed part. Search id: {$searchResult->getId()}.");
            $pointsHistory->setInternalData([
                "searchId"         => $searchResult->getId(),
                "externalSearchId" => $searchResult->getExternalExtractionId(),
            ]);

            $user->addPoints($pointsAmount);
            $pointsHistory->setAmountNow($user->getPointsAmount());
            $user->addPointHistory($pointsHistory);
            $searchResult->setReturnedPointsHistory($pointsHistory);

            $this->entityManager->persist($pointsHistory);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            $this->io->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param JobSearchResult $entry
     *
     * @return int
     */
    private function getReturnedPointsByStatus(JobSearchResult $entry): int
    {
        switch ($entry->getStatus()) {
            case SearchResultStatusEnum::ERROR->name:
                return $this->calculateReturnedPointsForError($entry);

            case SearchResultStatusEnum::PARTIALY_DONE->name:
            case SearchResultStatusEnum::DONE->name:
                return $this->calculateReturnedPointsForDone($entry);

            default:
                throw new LogicException("This status is not supported: {$entry->getStatus()}");
        }
    }

}