<?php

namespace App\Service\Dashboard;

use App\Action\Dashboard\DashboardAction;
use App\Entity\Security\User;
use App\Repository\Dashboard\DashboardRepository;
use App\Response\Dashboard\GetDashboardDataResponse;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Security\JwtAuthenticationService;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides data for {@see DashboardAction::getDashboardData()}
 */
class DashboardDataProviderService
{
    private const RECENT_FAILED_JOB_SEARCHES_HOURS_OFFSET = 48;

    public function __construct(
        private readonly DashboardRepository      $dashboardRepository,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly JobSearchService         $jobSearchService
    ) {
    }

    /**
     * Builds and returns the whole response used on front for building dashboard
     *
     * @return GetDashboardDataResponse
     * @throws GuzzleException
     */
    public function getResponse(): GetDashboardDataResponse
    {
        $user     = $this->jwtAuthenticationService->getUserFromRequest();
        $response = GetDashboardDataResponse::buildOkResponse();

        $pendingJobSearches      = $this->dashboardRepository->findPendingJobSearches($user);
        $recentFailedJobSearches = $this->dashboardRepository->findRecentFailedJobSearches($user, self::RECENT_FAILED_JOB_SEARCHES_HOURS_OFFSET);

        $pendingJobSearchesCount      = count($pendingJobSearches);
        $recentFailedJobSearchesCount = count($recentFailedJobSearches);

        $pointsSpentTotally = $user->countPointsSpent();
        $pointsAvailable    = $user->getPointsAmount();

        $applicationsPerDay      = $this->getApplicationsPerDay($user);
        $uniqueOffersFoundPerDay = $this->getUniqueOffersPerDay($user);

        $response->setPointsAvailable($pointsAvailable);
        $response->setUniqueOffersFoundPerDay($uniqueOffersFoundPerDay);
        $response->setPointsSpentTotally($pointsSpentTotally);
        $response->setPendingJobSearchesCount($pendingJobSearchesCount);
        $response->setRecentFailedJobSearchesCount($recentFailedJobSearchesCount);
        $response->setRecentFailedJobSearchesHoursOffset(self::RECENT_FAILED_JOB_SEARCHES_HOURS_OFFSET);
        $response->setJobApplicationsPerDay($applicationsPerDay);

        return $response;
    }

    /**
     * Returns the job applications grouped by days
     *
     * @param User $user
     *
     * @return array
     */
    private function getApplicationsPerDay(User $user): array
    {
        $applicationsPerMonths = $this->dashboardRepository->getCountOfApplicationPerDay($user);
        $normalizedDataArray   = [];

        foreach ($applicationsPerMonths as $data) {
            $day   = $data['day'];
            $year  = $data['year'];
            $month = $data['month'];
            $count = $data['applicationCount'];

            $normalizedDataArray[] = [
                "year"  => $year,
                "month" => $month,
                "day"   => $day,
                "count" => $count,
            ];
        }

        return $normalizedDataArray;
    }

    /**
     * Returns the job applications grouped by days
     *
     * @param User $user
     *
     * @return array
     *
     * @throws GuzzleException
     */
    private function getUniqueOffersPerDay(User $user): array
    {
        $uniqueOffersFoundPerDay = $this->jobSearchService->getCountOfUniquePerDay($user->getExternalExtractionIds());
        $normalizedDataArray     = [];

        foreach ($uniqueOffersFoundPerDay as $dto) {
            $normalizedDataArray[] = [
                "year"  => $dto->getYear(),
                "month" => $dto->getMonth(),
                "day"   => $dto->getDay(),
                "count" => $dto->getOffersCount(),
            ];
        }

        return $normalizedDataArray;
    }

}