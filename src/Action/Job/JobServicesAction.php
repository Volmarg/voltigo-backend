<?php

namespace App\Action\Job;

use App\Entity\Job\JobSearchResult;
use App\Response\Job\Service\SupportedCountriesResponse;
use App\Service\Api\JobSearcher\JobSearchService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Action related to the {@see JobSearchResult}
 */
class JobServicesAction extends AbstractController
{
    public function __construct(
        private readonly JobSearchService $jobSearchService
    ) {
    }

    /**
     * Provides the supported country names for which offers can be searched for
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    #[Route("/job-services/get-supported-country-names", name: "job.services.get.supported.country.names", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function dispatchedSearchResults(): JsonResponse
    {
        $countryNames = $this->jobSearchService->getSupportedAreaNames();

        $response = SupportedCountriesResponse::buildOkResponse();
        $response->setCountryNames($countryNames);

        return $response->toJsonResponse();
    }

}