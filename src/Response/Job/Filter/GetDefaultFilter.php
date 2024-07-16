<?php

namespace App\Response\Job\Filter;

use App\Response\Base\BaseResponse;

/**
 * Response delivering the default filter configuration
 */
class GetDefaultFilter extends BaseResponse
{
    /**
     * @var array $filterData
     */
    private array $filterData;

    /**
     * @return array
     */
    public function getFilterData(): array
    {
        return $this->filterData;
    }

    /**
     * @param array $filterData
     */
    public function setFilterData(array $filterData): void
    {
        $this->filterData = $filterData;
    }

}