<?php

namespace App\Controller\Job;

use App\Entity\Job\JobOfferInformation;
use App\Repository\Job\JobOfferInformationRepository;

/**
 * Controller for {@see JobOfferInformation}
 */
class JobOfferInformationController
{
    public function __construct(
        private JobOfferInformationRepository $jobOfferInformationRepository
    ){}

    /**
     * Will return one {@see JobOfferInformation} or null (for external id)
     * @param int $id
     * @return JobOfferInformation|null
     */
    public function findByExternalId(int $id): ?JobOfferInformation
    {
        return $this->jobOfferInformationRepository->findOneBy([
            "externalId" => $id,
        ]);
    }

}