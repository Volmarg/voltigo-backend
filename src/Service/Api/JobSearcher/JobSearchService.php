<?php

namespace App\Service\Api\JobSearcher;

use App\DTO\Frontend\JobOffer\Filter\FilterDTO;
use App\Entity\Job\JobApplication;
use App\Entity\Job\JobSearchResult;
use JobSearcherBridge\DTO\Statistic\JobSearch\CountOfUniquePerDayDto;
use JobSearcherBridge\Request\Extraction\GetExtractionsMinimalDataRequest;
use JobSearcherBridge\Request\Offer\GetDescriptionRequest;
use App\Service\Api\JobSearcher\Filter\FilterService;
use App\Service\Security\JwtAuthenticationService;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcherBridge\DTO\Offers\Exclusion\ExcludedOfferData;
use JobSearcherBridge\DTO\Offers\Filter\JobOfferFilterDto;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;
use JobSearcherBridge\Request\GetOffersForExtractionRequest;
use JobSearcherBridge\Request\JobServices\GetSupportedAreasRequest;
use JobSearcherBridge\Request\Offer\GetSingleOfferRequest;
use JobSearcherBridge\Request\PingRequest;
use JobSearcherBridge\Request\Statistic\Offer\GetCountOfUniquePerDayRequest;
use JobSearcherBridge\Response\BaseResponse;
use JobSearcherBridge\Response\Extraction\GetExtractionsMinimalDataResponse;
use JobSearcherBridge\Response\GetOffersForExtractionResponse;
use JobSearcherBridge\Service\BridgeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use TypeError;
use JobSearcherBridge\Request\Extraction\CountRunningExtractionsRequest;
use JobSearcherBridge\Response\Extraction\CountRunningExtractionsResponse;

/**
 * Provides job offers
 */
class JobSearchService
{

    private string $baseUrl;

    public function __construct(
        private readonly BridgeService               $jobSearcherBridgeService,
        private readonly FilterService               $filterService,
        private readonly JwtAuthenticationService    $jwtAuthenticationService,
        private readonly ParameterBagInterface       $parameterBag,
        private readonly LoggerInterface             $logger
    )
    {
        $this->baseUrl = $parameterBag->get('job_search_service.base_url');
    }

    /**
     * Will return the job offers from job-searcher api,
     *
     * @param JobSearchResult        $jobSearchResult
     * @param JobOfferFilterDto|null $filter
     *
     * @return GetOffersForExtractionResponse
     * @throws GuzzleException
     */
    public function getOffersFromApi(JobSearchResult $jobSearchResult, ?JobOfferFilterDto $filter = null): GetOffersForExtractionResponse
    {
        $user   = $this->jwtAuthenticationService->getUserFromRequest();
        $filter = ($filter ?: $this->filterService->buildSearchFilterFromJobSearch($jobSearchResult));

        $daysOffset = $this->parameterBag->get('exclude_applied_on_in_last_days');
        $minDate    = (new DateTime())->modify("-{$daysOffset} DAYS");

        $request = new GetOffersForExtractionRequest(Request::METHOD_POST, $this->baseUrl);
        $request->setExtractionId($jobSearchResult->getExternalExtractionId());
        $request->setFilter($filter);

        $appliedOffers = $user->getAppliedOffersAfterDate($minDate);
        $exclusionDtos = array_map(
            function (JobApplication $application) {
                return new ExcludedOfferData(
                    $application->getJobOffer()->getTitle(),
                    $application->getJobOffer()->getCompanyName(),
                    $application->getJobOffer()->getExternalId(),
                );
            },
            $appliedOffers
        );

        $request->setExcludedOffersData($exclusionDtos);
        $request->setUserExtractionIds($jobSearchResult->getUser()->getExternalExtractionIds());

        $response = $this->jobSearcherBridgeService->getOffersForExtraction($request);

        $this->logInvalidResponse($response);

        return $response;
    }

    /**
     * Will return {@see JobOfferAnalyseResultDto}
     *
     * @param int       $externalOfferId
     * @param FilterDTO $filterDTO
     *
     * @return JobOfferAnalyseResultDto
     * @throws GuzzleException
     * @throws Exception
     */
    public function getSingleOffer(int $externalOfferId, FilterDTO $filterDTO): JobOfferAnalyseResultDto
    {
        $searcherFilterDto = $this->filterService->buildSearcherFilterFromFrontFilter($filterDTO);
        $request           = new GetSingleOfferRequest(Request::METHOD_POST, $this->baseUrl);
        $request->setOfferId($externalOfferId);
        $request->setFilter($searcherFilterDto);

        $response = $this->jobSearcherBridgeService->getSingleOffer($request);

        $this->logInvalidResponse($response);

        return $response->getOffer();
    }

    /**
     * Will return {@see GetExtractionsMinimalDataResponse}
     *
     * @param array $extractionIds
     * @param array $searchIds
     *
     * @return GetExtractionsMinimalDataResponse
     * @throws GuzzleException
     */
    public function getExtractionsMinimalData(array $extractionIds = [], array $searchIds = []): GetExtractionsMinimalDataResponse
    {
        $request = new GetExtractionsMinimalDataRequest(Request::METHOD_POST, $this->baseUrl);
        $request->setExtractionIds($extractionIds);
        $request->setClientSearchIds($searchIds);

        $response = $this->jobSearcherBridgeService->getExtractionsMinimalData($request);

        $this->logInvalidResponse($response);

        return $response;
    }

    /**
     * Will ping the job searcher service
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function pingService(): bool
    {
        try {
            $request     = new PingRequest(Request::METHOD_GET, $this->baseUrl);
            $apiResponse = $this->jobSearcherBridgeService->ping($request);
        } catch (Exception|TypeError) {
            return false;
        }

        return $apiResponse->isSuccess();
    }

    /**
     * Get count of extractions running in `X` last hours
     *
     * @param int $hoursOffset
     *
     * @return int
     * @throws GuzzleException
     */
    public function getCountOfActiveSearches(int $hoursOffset): int
    {
        $request = new CountRunningExtractionsRequest(Request::METHOD_GET, $this->baseUrl);
        $request->setHoursOffset($hoursOffset);

        $response = $this->jobSearcherBridgeService->countRunningExtractions($request);

        return $response->getCount();
    }

    /**
     * Will return the area / countries supported by the job searcher
     *
     * @return array
     * @throws GuzzleException
     */
    public function getSupportedAreaNames(): array
    {
        $request  = new GetSupportedAreasRequest(Request::METHOD_GET, $this->baseUrl);
        $response = $this->jobSearcherBridgeService->getSupportedAreaNames($request);
        $areas    = $response->getAreaNames();

        $this->logInvalidResponse($response);

        return $areas;
    }

    /**
     * Will return full offer description
     * - necessary as the descriptions are often over 2k / 5k long,
     * - the filters are also needed because without them the offer keywords won't get highlighted
     *
     * @param int $offerId
     * @param JobOfferFilterDto $filter
     * @return string
     * @throws GuzzleException
     */
    public function getDescription(int $offerId, JobOfferFilterDto $filter): string
    {
        $request = new GetDescriptionRequest(Request::METHOD_POST, $this->baseUrl);
        $request->setOfferId($offerId);
        $request->setFilter($filter);

        $response = $this->jobSearcherBridgeService->getOfferDescription($request);

        $description = $response->getDescription();

        $this->logInvalidResponse($response);

        return $description;
    }

    /**
     * Will return count of unique offers found per day of month in year, for given extraction ids
     *
     * @param array $extractionIds
     *
     * @return CountOfUniquePerDayDto[]
     * @throws GuzzleException
     */
    public function getCountOfUniquePerDay(array $extractionIds): array
    {
        $request = new GetCountOfUniquePerDayRequest(Request::METHOD_POST, $this->baseUrl);
        $request->setExtractionIds($extractionIds);

        $response = $this->jobSearcherBridgeService->getCountOfUniquePerDay($request);

        $this->logInvalidResponse($response);

        return $response->getDtos();
    }

    /**
     * @param BaseResponse $baseResponse
     */
    private function logInvalidResponse(BaseResponse $baseResponse): void
    {
        if (!$baseResponse->isSuccess()) {
            $this->logger->critical("Invalid response from job searcher", [
                "invalidFields" => $baseResponse->getInvalidFields(),
                "message"       => $baseResponse->getMessage(),
                "code"          => $baseResponse->getCode(),
            ]);
        }
    }
}