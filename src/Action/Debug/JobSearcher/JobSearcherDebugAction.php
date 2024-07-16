<?php

namespace App\Action\Debug\JobSearcher;

use App\Entity\Job\JobSearchResult;
use App\Response\Job\GetJobOffers;
use App\Response\Job\Service\PingResponse;
use App\Response\Job\Service\SupportedCountriesResponse;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Serialization\ObjectSerializerService;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Special class containing logic for testing, either dev or prod (not accessible for standard users)
 * Related to the: {@see JobSearchService}
 *
 * Class DebugAction
 * @package App\Action
 */
#[Route("/debug", name: "debug_")]
class JobSearcherDebugAction extends AbstractController
{

    public function __construct(
        private readonly JobSearchService        $jobSearchService,
        private readonly ObjectSerializerService $objectSerializerService
    )
    {}

    /**
     * Route for pinging the job searcher
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    #[Route("/job-offers-handler/ping", name: "test.job.offers.handler.ping", methods: [Request::METHOD_GET])]
    public function jobOffersHandlerPing(): JsonResponse
    {
        $isOk = $this->jobSearchService->pingService();

        $response = PingResponse::buildOkResponse();
        $response->setData(['isOk' => $isOk]);

        return $response->toJsonResponse();
    }

    /**
     * Route for getting the job offers from job searcher
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    #[Route("/job-offers-handler/get-offers/{id}", name: "test.job.offers.handler.get.offers", methods: [Request::METHOD_GET])]
    public function jobOffersHandlerGetOffer(JobSearchResult $jobSearchResult): JsonResponse
    {
        $response = $this->jobSearchService->getOffersFromApi($jobSearchResult);
        $offers   = $response->getOffers();

        $offersArray = array_map(
            fn(JobOfferAnalyseResultDto $analyseResultDto) => $this->objectSerializerService->toArray($analyseResultDto),
            $offers
        );

        $response = GetJobOffers::buildOkResponse();
        $response->setJobOffersDataArray($offersArray);

        return $response->toJsonResponse();
    }

    /**
     * Route for getting the job offers from job searcher
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    #[Route("/job-offers-handler/get-supported-country-names", name: "test.job.offers.handler.get.supported.country.names", methods: [Request::METHOD_GET])]
    public function jobOffersHandlerGetSupportedCountryNames(): JsonResponse
    {
        $countryNames = $this->jobSearchService->getSupportedAreaNames();

        $response = SupportedCountriesResponse::buildOkResponse();
        $response->setCountryNames($countryNames);

        return $response->toJsonResponse();
    }

}