<?php

namespace App\Response\Job;

use App\Response\Base\BaseResponse;

/**
 * Response delivering job offers for front
 */
class GetJobOffers extends BaseResponse
{
    /**
     * @var array $filterValuesDataArray
     */
    private array $filterValuesDataArray;

    /**
     * @var array $jobOffersDataArray
     */
    private array $jobOffersDataArray;

    /**
     * @var int $allFoundOffersCount
     */
    private int $allFoundOffersCount;

    /**
     * @var int $returnedOffersCount
     */
    private int $returnedOffersCount;

    /**
     * @var int $appliedOffersCount
     */
    private int $appliedOffersCount;

    /**
     * @return array
     */
    public function getJobOffersDataArray(): array
    {
        return $this->jobOffersDataArray;
    }

    /**
     * @param array $jobOffersDataArray
     */
    public function setJobOffersDataArray(array $jobOffersDataArray): void
    {
        $this->jobOffersDataArray = $jobOffersDataArray;
    }

    /**
     * @return int
     */
    public function getAllFoundOffersCount(): int
    {
        return $this->allFoundOffersCount;
    }

    /**
     * @param int $allFoundOffersCount
     */
    public function setAllFoundOffersCount(int $allFoundOffersCount): void
    {
        $this->allFoundOffersCount = $allFoundOffersCount;
    }

    /**
     * @return int
     */
    public function getReturnedOffersCount(): int
    {
        return $this->returnedOffersCount;
    }

    /**
     * @param int $returnedOffersCount
     */
    public function setReturnedOffersCount(int $returnedOffersCount): void
    {
        $this->returnedOffersCount = $returnedOffersCount;
    }

    /**
     * @return array
     */
    public function getFilterValuesDataArray(): array
    {
        return $this->filterValuesDataArray;
    }

    /**
     * @param array $filterValuesDataArray
     */
    public function setFilterValuesDataArray(array $filterValuesDataArray): void
    {
        $this->filterValuesDataArray = $filterValuesDataArray;
    }

    public function getAppliedOffersCount(): int
    {
        return $this->appliedOffersCount;
    }

    public function setAppliedOffersCount(int $appliedOffersCount): void
    {
        $this->appliedOffersCount = $appliedOffersCount;
    }

}