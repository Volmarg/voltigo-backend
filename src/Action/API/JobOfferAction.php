<?php

namespace App\Action\API;

use App\Repository\Job\JobOfferInformationRepository;
use App\Response\Api\BaseApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handling of offer search / application logic toward/from external systems
 */
#[Route("/api/job-offer", name: "api.job_offer.")]
class JobOfferAction extends AbstractController
{

    public function __construct(
        private readonly JobOfferInformationRepository $jobOfferInformationRepository
    )
    {
    }

    /**
     * Will check if offer of given external id (internal inside the system which calls this route).
     * is referenced somewhere inside this project.
     *
     * @param int $externalId
     *
     * @return JsonResponse
     */
    #[Route("/is-offer-used/{externalId}", name: "is_offer_used", methods: Request::METHOD_GET)]
    public function updateFromRequest(int $externalId): JsonResponse
    {
        $offerInformation = $this->jobOfferInformationRepository->findBy([
            'externalId' => $externalId,
        ]);

        if (!empty($offerInformation)) {
            return (new BaseApiResponse())->toJsonResponse();
        }

        return BaseApiResponse::buildNotFoundRequestResponse()->toJsonResponse();
    }

}