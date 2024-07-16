<?php

namespace App\Response\Dashboard;

use App\Action\Dashboard\DashboardAction;
use App\Response\Base\BaseResponse;

/**
 * Delivers data from {@see DashboardAction::getDashboardData()}
 */
class GetDashboardDataResponse extends BaseResponse
{

    /**
     * @var array $jobApplicationsPerDay
     */
    private array $jobApplicationsPerDay = [];

    /**
     * @var array $uniqueOffersFoundPerDay
     */
    private array $uniqueOffersFoundPerDay = [];

    /**
     * @var int $recentFailedJobSearchesHoursOffset
     */
    private int $recentFailedJobSearchesHoursOffset;

    /**
     * @var int $pendingJobSearchesCount
     */
    private int $pendingJobSearchesCount;

    /**
     * @var int $recentFailedJobSearchesCount
     */
    private int $recentFailedJobSearchesCount;

    /**
     * @var int $pointsSpentTotally
     */
    private int $pointsSpentTotally;

    /**
     * @var int $pointsAvailable
     */
    private int $pointsAvailable;

    /**
     * @return int
     */
    public function getPendingJobSearchesCount(): int
    {
        return $this->pendingJobSearchesCount;
    }

    /**
     * @param int $pendingJobSearchesCount
     */
    public function setPendingJobSearchesCount(int $pendingJobSearchesCount): void
    {
        $this->pendingJobSearchesCount = $pendingJobSearchesCount;
    }

    /**
     * @return int
     */
    public function getRecentFailedJobSearchesCount(): int
    {
        return $this->recentFailedJobSearchesCount;
    }

    /**
     * @param int $recentFailedJobSearchesCount
     */
    public function setRecentFailedJobSearchesCount(int $recentFailedJobSearchesCount): void
    {
        $this->recentFailedJobSearchesCount = $recentFailedJobSearchesCount;
    }

    /**
     * @return int
     */
    public function getPointsSpentTotally(): int
    {
        return $this->pointsSpentTotally;
    }

    /**
     * @param int $pointsSpentTotally
     */
    public function setPointsSpentTotally(int $pointsSpentTotally): void
    {
        $this->pointsSpentTotally = $pointsSpentTotally;
    }

    /**
     * @return int
     */
    public function getPointsAvailable(): int
    {
        return $this->pointsAvailable;
    }

    /**
     * @param int $pointsAvailable
     */
    public function setPointsAvailable(int $pointsAvailable): void
    {
        $this->pointsAvailable = $pointsAvailable;
    }

    /**
     * @return int
     */
    public function getRecentFailedJobSearchesHoursOffset(): int
    {
        return $this->recentFailedJobSearchesHoursOffset;
    }

    /**
     * @param int $recentFailedJobSearchesHoursOffset
     */
    public function setRecentFailedJobSearchesHoursOffset(int $recentFailedJobSearchesHoursOffset): void
    {
        $this->recentFailedJobSearchesHoursOffset = $recentFailedJobSearchesHoursOffset;
    }

    /**
     * @return array
     */
    public function getJobApplicationsPerDay(): array
    {
        return $this->jobApplicationsPerDay;
    }

    /**
     * @param array $jobApplicationsPerDay
     */
    public function setJobApplicationsPerDay(array $jobApplicationsPerDay): void
    {
        $this->jobApplicationsPerDay = $jobApplicationsPerDay;
    }

    /**
     * @return array
     */
    public function getUniqueOffersFoundPerDay(): array
    {
        return $this->uniqueOffersFoundPerDay;
    }

    /**
     * @param array $uniqueOffersFoundPerDay
     */
    public function setUniqueOffersFoundPerDay(array $uniqueOffersFoundPerDay): void
    {
        $this->uniqueOffersFoundPerDay = $uniqueOffersFoundPerDay;
    }

}