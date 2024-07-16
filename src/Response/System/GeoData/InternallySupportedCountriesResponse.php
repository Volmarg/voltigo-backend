<?php

namespace App\Response\System\GeoData;

use App\Action\System\SystemGeoDataAction;
use App\DTO\Geo\CountryDataDto;
use App\Response\Base\BaseResponse;

/**
 * Response for {@see SystemGeoDataAction::getInternallySupportedCountries()}
 */
class InternallySupportedCountriesResponse extends BaseResponse
{
    /**
     * @var CountryDataDto[]
     */
    private array $countriesData = [];

    /**
     * @return array
     */
    public function getCountriesData(): array
    {
        return $this->countriesData;
    }

    /**
     * @param array $countriesData
     */
    public function setCountriesData(array $countriesData): void
    {
        $this->countriesData = $countriesData;
    }
}