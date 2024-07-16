<?php

namespace App\Action\Currency;

use App\Response\Base\BaseResponse;
use App\Response\Currency\GetAllCurrencyCodesResponse;
use App\Response\Currency\GetExchangeRate;
use App\Service\Api\FinancesHub\FinancesHubService;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides endpoints for uploaded file related logic
 */
class CurrencyAction extends AbstractController
{

    public function __construct(
        private readonly FinancesHubService $financesHubService
    ) {
    }

    /**
     * Provides exchange rate between 2 currencies
     *
     * @param string $fromCurrency
     * @param string $targetCurrency
     *
     * @return JsonResponse
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    #[Route("/currency/exchange-rate/get/{fromCurrency}/{targetCurrency}", name: "currency.exchange_rate.get", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getExchangeRate(string $fromCurrency, string $targetCurrency): JsonResponse
    {
        try {
            $exchangeRate = $this->financesHubService->getExchangeRate($fromCurrency, $targetCurrency);
        } catch (FinancesHubBridgeException $fhde) {
            if ($fhde->getCode() >= 400 && $fhde->getCode() < 500) {
                return (BaseResponse::buildBadRequestErrorResponse($fhde->getMessage()))->toJsonResponse();
            }

            throw $fhde;
        }

        $response = GetExchangeRate::buildOkResponse();
        $response->setExchangeRate($exchangeRate);

        return $response->toJsonResponse();
    }

    /**
     * Provides all the currency codes
     *
     * @return JsonResponse
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    #[Route("/currency/get-all-codes", name: "currency.get_all_codes", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getAllCurrencyCodes(): JsonResponse
    {
        try {
            $currencyCodes = $this->financesHubService->getAllCurrencyCodes();
        } catch (FinancesHubBridgeException $fhde) {
            if ($fhde->getCode() >= 400 && $fhde->getCode() < 500) {
                return (BaseResponse::buildBadRequestErrorResponse($fhde->getMessage()))->toJsonResponse();
            }

            throw $fhde;
        }

        $response = GetAllCurrencyCodesResponse::buildOkResponse();
        $response->setCurrencyCodes($currencyCodes);

        return $response->toJsonResponse();
    }

}