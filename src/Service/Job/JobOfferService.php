<?php

namespace App\Service\Job;

use App\Entity\Security\User;
use App\Service\Api\BlacklistHub\BlacklistHubService;
use App\Service\Api\JobSearcher\JobSearchService;
use BlacklistHubBridge\Dto\Email\EmailBlacklistSearchDto;
use BlacklistHubBridge\Exception\BlacklistHubBridgeException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcherBridge\DTO\Offers\Filter\JobOfferFilterDto;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;

/**
 * Service with logic for job offers
 */
class JobOfferService
{
    public function __construct(
        private readonly BlacklistHubService $blacklistHubService
    ) {

    }

    /**
     * Will filter the offers with some internal rules,
     * This has nothing in common with the:
     * - {@see JobSearchService}
     *
     * as that uses its own filtering rules internally and relies on the {@see JobOfferFilterDto}
     *
     * @param JobOfferAnalyseResultDto[] $offers
     *
     * @return JobOfferAnalyseResultDto[]
     * @throws BlacklistHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function filterOffers(array $offers, User $user): array
    {
        $filteredOffers           = [];
        $emailBlacklistSearchDtos = [];
        foreach ($offers as $offer) {
            if (empty($offer->getContactDetail()->getEmail())) {
                continue;
            }

            $emailBlacklistSearchDtos[] = new EmailBlacklistSearchDto(
                $offer->getContactDetail()->getEmail(),
                $user->getEmail()
            );
        }

        $blacklistStatusesResponse = $this->blacklistHubService->getEmailsStatuses($emailBlacklistSearchDtos);
        foreach ($offers as $offer) {
            if (empty($offer->getContactDetail()->getEmail())) {
                $filteredOffers[] = $offer;
                continue;
            }

            if ($blacklistStatusesResponse->isRecipientBlacklisted($offer->getContactDetail()->getEmail())) {
                continue;
            }

            $filteredOffers[] = $offer;
        }

        return $filteredOffers;
    }
}