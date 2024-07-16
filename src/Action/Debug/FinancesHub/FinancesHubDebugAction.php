<?php

namespace App\Action\Debug\FinancesHub;

use App\Response\Base\BaseResponse;
use App\Service\Api\FinancesHub\FinancesHubService;
use FinancesHubBridge\Dto\Customer;
use FinancesHubBridge\Dto\Product;
use FinancesHubBridge\Dto\Transaction;
use FinancesHubBridge\Enum\PaymentToolEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use symfony\Component\Routing\Annotation\Route;

/**
 * Debugging the finances hub calls {@see FinancesHubService}
 */
class FinancesHubDebugAction
{
    public function __construct(
        private readonly FinancesHubService  $financesHubService,
    ){}

    /**
     * @return JsonResponse
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    #[Route("/debug/finances-hub/insert-transaction", name: "debug.finances.hub.insert.transaction", methods: [Request::METHOD_GET])]
    public function insertTransaction(): JsonResponse
    {
        $transaction = new Transaction();
        $customer    = new Customer();
        $product     = new Product();

        $product->setCurrency("EUR");
        $product->setQuantity(2);
        $product->setTaxPercentage(15);
        $product->setCost(12.56);
        $product->setName("article name");
        $product->setIncludeTax(true);;

        $customer->setName("Volmarg");
        $customer->setCountry("Poland");
        $customer->setZip(57074);
        $customer->setCity("City");
        $customer->setAddress("street 1/12c");
        $customer->setUserId(1);

        $transaction->setPaymentTool(PaymentToolEnum::PAYPAL->value);
        $transaction->setProducts([$product]);
        $transaction->setCustomer($customer);

        $this->financesHubService->insertTransaction($transaction);

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * @param string $fromCurrency
     * @param string $targetCurrency
     *
     * @return JsonResponse
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    #[Route("/debug/finances-hub/get-exchange-rate/{fromCurrency}/{targetCurrency}", name: "debug.finances.hub.get_exchange_rate", methods: [Request::METHOD_GET])]
    public function getExchangeRate(string $fromCurrency, string $targetCurrency): JsonResponse
    {
        $exchangeRate = $this->financesHubService->getExchangeRate($fromCurrency, $targetCurrency);
        $baseResponse = BaseResponse::buildOkResponse();
        $baseResponse->addData("exchangeRate", $exchangeRate);

        return $baseResponse->toJsonResponse();
    }

}