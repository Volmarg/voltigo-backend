<?php

namespace App\Action\Job\Filter;

use App\Entity\Job\JobSearchResult;
use App\Response\Job\Filter\GetDefaultFilter;
use App\Service\Api\JobSearcher\Filter\FilterService;
use App\Service\Serialization\ObjectSerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the api calls related to the filter visible on the job offer search result details
 */
class JobSearchResultFilterAction extends AbstractController
{
    public function __construct(
        private readonly FilterService           $filterService,
        private readonly ObjectSerializerService $objectSerializerService
    ){}

    #[Route("/job-offers/search-result/filter/default/{id}", name: "job_offers.search_result.filter.default", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getDefaultFilter(JobSearchResult $jobSearchResult): JsonResponse
    {
        $filterValues = $this->filterService->buildSearchFilterFromJobSearch($jobSearchResult);
        $frontFilter  = $this->filterService->buildFrontFilterFromSearcherFilter($filterValues);
        $filterData   = $this->objectSerializerService->toArray($frontFilter);
        $response     = GetDefaultFilter::buildOkResponse();

        $response->setFilterData($filterData);

        return $response->toJsonResponse();
    }
}