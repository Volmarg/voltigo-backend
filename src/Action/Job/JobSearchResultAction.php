<?php

namespace App\Action\Job;

use App\Controller\Core\Services;
use App\DTO\Internal\Job\JobSearchDTO;
use App\DTO\RabbitMq\Producer\JobSearch\Start\ParameterBag;
use App\Entity\Ecommerce\PointShopProduct;
use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Job\JobSearchResult;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Exception\NotFoundException;
use App\Exception\Payment\PointShop\NotEnoughPointsException;
use App\Form\Job\JobSearchForm;
use App\Repository\Job\JobSearchResultRepository;
use App\Response\Base\BaseResponse;
use App\Response\Job\GetJobSearchResultsResponse;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Job\JobSearchResultService;
use App\Service\PointShop\PointShopProductPaymentService;
use App\Service\RabbitMq\JobSearcher\JobSearchStartProducerService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\System\Restriction\JobSearchRestrictionService;
use App\Service\System\State\SystemStateService;
use App\Service\Validation\ValidationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Action related to the {@see JobSearchResult}
 */
class JobSearchResultAction extends AbstractController
{
    public function __construct(
        private readonly Services                       $services,
        private readonly JobSearchResultRepository      $jobSearchResultRepository,
        private readonly JobSearchStartProducerService  $jobSearchStartProducerService,
        private readonly PointShopProductPaymentService $pointShopProductPaymentService,
        private readonly ObjectSerializerService        $objectSerializerService,
        private readonly EntityManagerInterface         $entityManager,
        private readonly JobSearchResultService         $jobSearchResultService,
        private readonly ValidationService              $validationService,
        private readonly ParameterBagInterface          $parameterBag,
        private readonly JobSearchRestrictionService    $jobSearchRestrictionService,
        private readonly TranslatorInterface            $translator,
        private readonly JobSearchService               $jobSearchService,
        private readonly SystemStateService             $systemStateService
    ) {
    }

    /**
     * Handles forwarding the job offers search request to job-search project via rabbit
     *
     * To control max pagination page see {@see ParameterBag::DEFAULT_MAX_PAGINATION_PAGE}
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     * @throws GuzzleException
     */
    #[Route("/job-offers/search", name: "job_offer_search", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function jobOffersSearch(Request $request): JsonResponse
    {
        if ($this->systemStateService->isSystemDisabled()) {
            return BaseResponse::buildMaintenanceResponse($this->translator->trans('state.disabled.downForMaintenance'))->toJsonResponse();
        }

        if ($this->jobSearchRestrictionService->isDisabled()) {
            $msg = $this->translator->trans('job.search.messages.disabled');
            return BaseResponse::buildBadRequestErrorResponse($msg)->toJsonResponse();
        }

        $user = $this->services->getJwtAuthenticationService()->getUserFromRequest();
        $form = $this->createForm(JobSearchForm::class);
        $form = $this->services->getFormService()->handlePostFormForAxiosCall($form, $request);

        /** @var JobSearchDTO $searchDto */
        $searchDto = $form->getData();
        $response  = $this->jobSearchResultService->checkIfCanSearchForJobs($searchDto);
        if (!empty($response)) {
            return $response->toJsonResponse();
        }

        $jobSearchResult = new JobSearchResult();
        $jobSearchResult->setUser($user);
        $jobSearchResult->setTargetAreas([$searchDto->getTargetArea()]);
        $jobSearchResult->setKeywords($searchDto->getJobSearchKeywords());
        $jobSearchResult->setLocationName($searchDto->getLocationName());
        $jobSearchResult->setMaxDistance($searchDto->getMaxDistance());
        $jobSearchResult->setOffersLimit($searchDto->getOffersLimit());

        $violationDto = $this->validationService->validateAndReturnArrayOfInvalidFieldsWithMessages($jobSearchResult);
        if (!$violationDto->isSuccess()) {
            return BaseResponse::buildInvalidFieldsRequestErrorResponse($violationDto->getViolationsWithMessages())->toJsonResponse();
        }

        try {
            $this->entityManager->beginTransaction();

            $this->entityManager->persist($jobSearchResult);
            $this->entityManager->flush();
            $userPointHistory = $this->buyJobSearchFromPointShop($jobSearchResult, $searchDto);
            $jobSearchResult->setUserPointHistory($userPointHistory);

            // that's correct - it has to be corrected and flushed twice
            $this->entityManager->persist($jobSearchResult);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $producerParameterBag = ParameterBag::buildFromJobSearchResult($jobSearchResult);
        $this->jobSearchStartProducerService->produce($producerParameterBag);

        $jobSearchResult->setStatus(SearchResultStatusEnum::WIP->name);

        $this->entityManager->persist($jobSearchResult);
        $this->entityManager->flush();

        $message = $this->services->getTranslator()->trans('job.search.messages.searchDispatched');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

    /**
     * Provides all the dispatched searches for user
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     */
    #[Route("/job-offers/search/dispatched", name: "job_offer_search_dispatched", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function dispatchedSearchResults(): JsonResponse
    {
        $now                = new DateTime();
        $user               = $this->services->getJwtAuthenticationService()->getUserFromRequest();
        $searchResults      = $this->jobSearchResultRepository->findForUser($user);
        $searchResultArrays = [];
        $maxValidityDays    = $this->parameterBag->get('offer_search_max_lifetime') / 24; # because the value of parameter is in hours
        $extractionIds      = array_map(
            fn(JobSearchResult $searchResult) => $searchResult->getExternalExtractionId(),
            $searchResults,
        );

        $minimalDataResponse = $this->jobSearchService->getExtractionsMinimalData($extractionIds);
        foreach ($searchResults as $searchResult) {
            $daysDiff = $now->diff($searchResult->getCreated())->format("%a");
            $daysLeft = $maxValidityDays - $daysDiff;

            $offersCount = null;
            if (!empty($searchResult->getExternalExtractionId())) {
                $offersCount = $minimalDataResponse->getOffersCountForExtractionId($searchResult->getExternalExtractionId());
            }

            $searchResult->setValidDaysNumber($daysLeft);
            $searchResult->setOffersFoundCount($offersCount);

            $searchResultArrays[] = $this->services->getObjectSerializerService()->toArray($searchResult);
        }

        $resultsDto = GetJobSearchResultsResponse::buildOkResponse();
        $resultsDto->setSearchResults($searchResultArrays);

        return $resultsDto->toJsonResponse();
    }

    /**
     * Handles "buying" the search result, deducting user points and setting some information that will be
     * either shown for user on front or will be used eventually for some debugging etc.
     *
     * Trying to avoid using words here, because even tho it's not planned to support other languages than English
     * it would create issue where some hardcoded words would be saved in database, or would require some mechanism
     * to translate parts on front.
     *
     * Furthermore, there can / will be more {@see PointShopProduct} and providing was translation system to support
     * properly would be an overkill
     *
     * @param JobSearchResult $jobSearchResult
     * @param JobSearchDTO    $searchDto
     *
     * @return UserPointHistory
     * @throws NotEnoughPointsException
     * @throws NotFoundException
     */
    private function buyJobSearchFromPointShop(JobSearchResult $jobSearchResult, JobSearchDTO $searchDto): UserPointHistory
    {
        $productIdEnum = PointShopProductPaymentService::mapSearchLimitToProductId($jobSearchResult->getOffersLimit());
        return $this->pointShopProductPaymentService->buy(
            $productIdEnum->name,
            $searchDto->countJobSearchKeywords(),
            $this->objectSerializerService->toArray($searchDto),
            ["Job search Id: {$jobSearchResult->getId()}"]
        );
    }

}