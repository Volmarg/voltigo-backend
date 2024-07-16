<?php

namespace App\Response\Job;

use App\Entity\Job\JobSearchResult;
use App\Response\Base\BaseResponse;

/**
 * Delivers {@see JobSearchResult} - the data shown on overview (not the job offers themselves)
 */
class GetJobSearchResultsResponse extends BaseResponse
{
    /**
     * @var array $searchResults
     */
    private array $searchResults = [];

    /**
     * @return array
     */
    public function getSearchResults(): array
    {
        return $this->searchResults;
    }

    /**
     * @param array $searchResults
     */
    public function setSearchResults(array $searchResults): void
    {
        $this->searchResults = $searchResults;
    }

}