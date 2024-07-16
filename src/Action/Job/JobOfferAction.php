<?php

namespace App\Action\Job;

use App\Controller\Job\JobApplicationController;
use App\DTO\Frontend\JobOffer\Filter\FilterDTO;
use App\Entity\Job\JobApplication;
use App\Entity\Job\JobSearchResult;
use App\Enum\Service\Serialization\SerializerType;
use App\Response\Job\GetFullDescription;
use App\Response\Job\GetJobOffers;
use App\Service\Api\JobSearcher\Filter\FilterService;
use App\Service\Api\JobSearcher\Handler\AppliedOffersHandlerService;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Api\JobSearcher\Provider\OffersFromFileProvider;
use App\Service\Job\JobOfferService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\Validation\ValidationService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides endpoints for job offer related logic
 */
class JobOfferAction extends AbstractController
{

    public function __construct(
        private readonly JobSearchService            $jobOffersService,
        private readonly JobApplicationController    $jobApplicationController,
        private readonly JwtAuthenticationService    $jwtAuthenticationService,
        private readonly ObjectSerializerService     $objectSerializerService,
        private readonly FilterService               $filterService,
        private readonly OffersFromFileProvider      $offersFromFileProvider,
        private readonly ValidationService           $validationService,
        private readonly JobOfferService             $jobOfferService,
        private readonly AppliedOffersHandlerService $appliedOffersHandlerService
    ) {
    }

    /**
     * Provides job offers which are matching the filters
     *
     * @param JobSearchResult $jobSearchResult
     * @param Request         $request
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/job-offers/filtered/{id}", name: "job_offer_get", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS, Request::METHOD_POST])]
    public function getFiltered(JobSearchResult $jobSearchResult, Request $request): JsonResponse
    {
        if (empty($jobSearchResult->getExternalExtractionId())) {
            throw new AccessDeniedException("Cannot call this logic if external extraction id is not yet set. Search result id: {$jobSearchResult->getId()}");
        }

        if (!$jobSearchResult->isDone()) {
            throw new AccessDeniedException("Cannot get job offers. Search is not marked as done! Search result id: {$jobSearchResult->getId()}");
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        $jobSearchResult->ensureBelongsToUser($user);

        $searcherFilter = null;
        if (
                !empty($request->getContent())
            &&  !empty(json_decode($request->getContent(), true))
        ) {
            /** @var FilterDTO $frontendFilter */
            $frontendFilter = $this->objectSerializerService->fromJson($request->getContent(), FilterDTO::class, SerializerType::CUSTOM);
            $frontendFilter->selfCorrect();

            $searcherFilter = $this->filterService->buildSearcherFilterFromFrontFilter($frontendFilter);
        }

        $searcherResponse   = $this->jobOffersService->getOffersFromApi($jobSearchResult, $searcherFilter);
        $jobOffers          = $this->appliedOffersHandlerService->handleApplications($searcherResponse->getOffers());
        $jobOffers          = $this->jobOfferService->filterOffers($jobOffers, $user);
        $jobOffersDataArray = array_map(
            fn(JobOfferAnalyseResultDto $analyseResultDto) => $this->objectSerializerService->toArray($analyseResultDto),
            $jobOffers
        );

        $filterValuesDataArray = $this->objectSerializerService->toArray($searcherResponse->getFilterValues());
        $appliedOffersCount    = count($user->getAppliedForSearch($jobSearchResult));

//         $jobOffersDataArray  = $this->getOffersFromFile();
        $jobOffersResponse   = GetJobOffers::buildOkResponse();
        $jobOffersResponse->setJobOffersDataArray($jobOffersDataArray);
        $jobOffersResponse->setAllFoundOffersCount($searcherResponse->getAllFoundOffersCount());
        $jobOffersResponse->setReturnedOffersCount($searcherResponse->getReturnedOffersCount());
        $jobOffersResponse->setAppliedOffersCount($appliedOffersCount);
        $jobOffersResponse->setFilterValuesDataArray($filterValuesDataArray);

        return $jobOffersResponse->toJsonResponse();
    }

    /**
     * Provides the offer full description
     *
     * @param int $offerId
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/job-offer/get-description/{offerId}", name: "job_offer_get.description", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function getDescription(int $offerId, Request $request): JsonResponse
    {
        $requestJson = $request->getContent();
        if (!$this->validationService->validateJson($requestJson)) {
            return (GetFullDescription::buildBadRequestErrorResponse())->toJsonResponse();
        }

        $requestData       = json_decode($requestJson, true);
        $filterValuesArray = $requestData['filterValues'];
        $filterValuesJson  = json_encode($filterValuesArray);

        /** @var FilterDTO $frontendFilter */
        $frontendFilter = $this->objectSerializerService->fromJson($filterValuesJson, FilterDTO::class, SerializerType::CUSTOM);
        $frontendFilter->selfCorrect();

        $searcherFilter = $this->filterService->buildSearcherFilterFromFrontFilter($frontendFilter);

        $description = $this->jobOffersService->getDescription($offerId, $searcherFilter);

        $response = GetFullDescription::buildOkResponse();
        $response->setDescription($description);

        return $response->toJsonResponse();
    }

    /**
     * Will return the offers loaded from file
     *
     * @return array
     * @throws Exception
     */
    private function getOffersFromFile(): array
    {
        $user                = $this->jwtAuthenticationService->getUserFromRequest();
        $excludedExternalIds = array_map(
            fn(JobApplication $jobApplication) => $jobApplication->getJobOffer()->getExternalId(),
            $this->jobApplicationController->findAllForUserInDaysInterval($user)
        );

        $jobOffersDataArray = $this->offersFromFileProvider->getOffersFromFile($excludedExternalIds);

        return $jobOffersDataArray;
    }

}