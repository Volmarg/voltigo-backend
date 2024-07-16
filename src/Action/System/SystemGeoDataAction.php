<?php

namespace App\Action\System;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\DTO\Geo\CountryDataDto;
use App\Enum\Address\CountryEnum;
use App\Response\System\GeoData\InternallySupportedCountriesResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * System geolocation data related endpoints
 */
class SystemGeoDataAction
{
    public const ROUTE_NAME_GET_INTERNALLY_SUPPORTED_COUNTRIES = "system.geo_data.get_internally_supported_countries";

    /**
     * Returns the internally supported countries list.
     * For more see: {@see CountryEnum}
     *
     * @return JsonResponse
     */
    #[Route("/system/geo-data/get-internally-supported-countries", name: self::ROUTE_NAME_GET_INTERNALLY_SUPPORTED_COUNTRIES, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function getInternallySupportedCountries(): JsonResponse
    {
        $response = InternallySupportedCountriesResponse::buildOkResponse();
        $dtos     = [];
        foreach (CountryEnum::cases() as $countryCode) {
            $countryDataDto = new CountryDataDto();
            $countryDataDto->setTwoDigitCode($countryCode->value);

            $dtos[] = $countryDataDto;
        }

        $response->setCountriesData($dtos);

        return $response->toJsonResponse();
    }
}