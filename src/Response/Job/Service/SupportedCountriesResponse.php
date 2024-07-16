<?php

namespace App\Response\Job\Service;

use App\Response\Base\BaseResponse;

class SupportedCountriesResponse extends BaseResponse
{
    /**
     * @var array $countryNames
     */
    private array $countryNames = [];

    /**
     * @return array
     */
    public function getCountryNames(): array
    {
        return $this->countryNames;
    }

    /**
     * @param array $countryNames
     */
    public function setCountryNames(array $countryNames): void
    {
        $this->countryNames = $countryNames;
    }

}
