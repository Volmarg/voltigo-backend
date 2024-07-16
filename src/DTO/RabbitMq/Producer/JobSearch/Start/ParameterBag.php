<?php

namespace App\DTO\RabbitMq\Producer\JobSearch\Start;

use App\Entity\Job\JobSearchResult;
use App\RabbitMq\Producer\JobSearcher\JobSearchStartProducer;

/**
 * Parameters bag used for {@see JobSearchStartProducer}
 */
class ParameterBag
{
    /**
     * This can eventually be changed in future by adding something similar like {@see JobSearchRestrictionService}
     */
    private const DEFAULT_MAX_PAGINATION_PAGE = 0; # starting from 0

    /**
     * @var string $keywords
     */
    private string $keywords;

    /**
     * @var int $maxPaginationPage
     */
    private int $maxPaginationPage = self::DEFAULT_MAX_PAGINATION_PAGE;

    /**
     * @var string|null $locationName
     */
    private ?string $locationName = null;

    /**
     * @var int|null $distance
     */
    private ?int $distance = null;

    private ?int $offersLimit = null;

    /**
     * @var string $country
     */
    private string $country;

    /**
     * @var int $searchId
     */
    private int $searchId;

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return int
     */
    public function getMaxPaginationPage(): int
    {
        return $this->maxPaginationPage;
    }

    /**
     * @param int $maxPaginationPage
     */
    public function setMaxPaginationPage(int $maxPaginationPage): void
    {
        $this->maxPaginationPage = $maxPaginationPage;
    }

    /**
     * @return string|null
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * @param string|null $locationName
     */
    public function setLocationName(?string $locationName): void
    {
        $this->locationName = $locationName;
    }

    /**
     * @return int|null
     */
    public function getDistance(): ?int
    {
        return $this->distance;
    }

    /**
     * @param int|null $distance
     */
    public function setDistance(?int $distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @return int
     */
    public function getSearchId(): int
    {
        return $this->searchId;
    }

    /**
     * @param int $searchId
     */
    public function setSearchId(int $searchId): void
    {
        $this->searchId = $searchId;
    }

    public function getOffersLimit(): ?int
    {
        return $this->offersLimit;
    }

    public function setOffersLimit(?int $offersLimit): void
    {
        $this->offersLimit = $offersLimit;
    }

    /**
     * @param JobSearchResult $jobSearchResult
     *
     * @return static
     */
    public static function buildFromJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        $parameterBag = new ParameterBag();
        $parameterBag->setLocationName($jobSearchResult->getLocationName());
        $parameterBag->setCountry($jobSearchResult->getFirstTargetArea());
        $parameterBag->setDistance($jobSearchResult->getMaxDistance());
        $parameterBag->setKeywords($jobSearchResult->getKeywordsAsString());
        $parameterBag->setSearchId($jobSearchResult->getId());
        $parameterBag->setOffersLimit($jobSearchResult->getOffersLimit());

        return $parameterBag;
    }

}