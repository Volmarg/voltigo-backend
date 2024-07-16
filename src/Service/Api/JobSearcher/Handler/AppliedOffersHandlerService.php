<?php

namespace App\Service\Api\JobSearcher\Handler;

use App\Entity\Job\JobApplication;
use App\Repository\Job\JobApplicationRepository;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;

/**
 * Handles checking if offers were already applied for and mark this in {@see JobOfferAnalyseResultDto}
 */
class AppliedOffersHandlerService
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository
    ){}

    /**
     * Will check if user already applied for any offers in past and if he did then the offer got special
     * flag set {@see JobOfferAnalyseResultDto::$appliedAt}
     *
     * Keep in mind that it's faster to fetch all the application and loop over them rather than doing multiple
     * db calls
     *
     * @param JobOfferAnalyseResultDto[] $offers
     * @return JobOfferAnalyseResultDto[]
     */
    public function handleApplications(array $offers): array
    {
        $applications = $this->getApplications($offers);
        $this->handleAppliedAt($offers, $applications);

        return $offers;
    }

    /**
     * @param array $offers
     *
     * @return JobApplication[]
     */
    private function getApplications(array $offers): array
    {
        $queryData = [];
        foreach ($offers as $offer) {
            $queryData[$offer->getCompanyDetail()->getCompanyName()] = $offer->getJobTitle();
        }

        $applications = $this->jobApplicationRepository->findByCompanyNamesAndTitles($queryData);

        return $applications;
    }

    /**
     * Will go over the applications, find the application matching for offer and set the {@see JobOfferAnalyseResultDto::$appliedAt}
     * based on the application creation date,
     *
     * Does not return anything since the appliedAt is set via reference
     *
     * @param JobOfferAnalyseResultDto[] $offers
     * @param JobApplication[] $applications
     */
    private function handleAppliedAt(array $offers, array $applications): void
    {
        foreach ($applications as $application) {
            $matchingOffers = array_filter(
                $offers,
                function (JobOfferAnalyseResultDto $offer) use ($application) {
                    $offerDataMd5   = md5($offer->getCompanyDetail()->getCompanyName() . $offer->getJobTitle());
                    $applicationMd5 = md5($application->getJobOffer()->getCompanyName() . $application->getJobOffer()->getTitle());

                    return ($offerDataMd5 === $applicationMd5);
                }
            );

            if (empty($matchingOffers)) {
                continue;
            }

            $matchingOffer = $matchingOffers[array_key_first($matchingOffers)];
            $matchingOffer->setAppliedAt($application->getCreated()->format("Y-m-d"));
        }
    }
}