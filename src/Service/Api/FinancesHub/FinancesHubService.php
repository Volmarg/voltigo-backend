<?php

namespace App\Service\Api\FinancesHub;

use Exception;
use FinancesHubBridge\Dto\Transaction;
use FinancesHubBridge\Enum\SourceEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use FinancesHubBridge\Request\Currency\GetAllCurrencyCodesRequest;
use FinancesHubBridge\Request\Currency\GetExchangeRateRequest;
use FinancesHubBridge\Request\InsertTransactionRequest;
use FinancesHubBridge\Request\Invoice\GeneratePdfRequest;
use FinancesHubBridge\Request\Payment\GetMinMaxTransactionDataRequest;
use FinancesHubBridge\Request\Payment\GetTaxPercentageRequest;
use FinancesHubBridge\Request\Payment\Stripe\CreatePaymentIntentRequest;
use FinancesHubBridge\Response\Payment\GetMinMaxTransactionDataResponse;
use FinancesHubBridge\Service\BridgeService;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Psr\Log\LoggerInterface;
use App\Entity\Ecommerce\Product\Product as EntityProduct;
use FinancesHubBridge\Dto\Product as BridgeProduct;
use TypeError;

/**
 * Service for handling connection with finances hub
 */
class FinancesHubService
{

    /**
     * @param BridgeService   $bridgeService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly BridgeService   $bridgeService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Will attempt to generate invoice pdf for transaction,
     * the pdf content is returned as response
     *
     * @param int $orderId
     *
     * @return string
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getInvoicePdfContent(int $orderId): string
    {
        $request = new GeneratePdfRequest();
        $request->setOrderId($orderId);
        $request->setCompanyName(SourceEnum::VOLTIGO->value);

        $response = $this->bridgeService->generatePdf($request);
        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException(
                "Could not generate pdf for transaction id: {$orderId}. Response message: {$response->getMessage()}",
                $response->getCode()
            );
        }

        return base64_decode($response->getPdfBase64Content());
    }

    /**
     * Inserts the transaction data to the finances hub
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function insertTransaction(Transaction $transaction): bool
    {
        try {
            $request = new InsertTransactionRequest();
            $request->setTransaction($transaction);

            $response = $this->bridgeService->insertTransaction($request);
        } catch (Exception $e) {
            $this->logger->critical("Exception while inserting transaction", [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return false;
        }

        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException("Could not insert transaction: {$response->getMessage()}");
        }

        return true;
    }

    /**
     * Get exchange rate for given currencies
     *
     * @param string $fromCurrency
     *
     * @param string $targetCurrency
     *
     * @return float
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getExchangeRate(string $fromCurrency, string $targetCurrency): float
    {
        return 1.0; // hardcoded for open source
        try {
            $request = new GetExchangeRateRequest();
            $request->setFromCurrency(mb_strtolower($fromCurrency));
            $request->setTargetCurrency(mb_strtolower($targetCurrency));

            $response = $this->bridgeService->getExchangeRate($request);
        } catch (Exception $e) {
            $this->logger->critical("Exception while getting exchange rate", [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return false;
        }

        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException("Could not get the exchange rate: {$response->getMessage()}");
        }

        return $response->getExchangeRate();
    }

    /**
     * Get all currency codes
     *
     * @return array
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getAllCurrencyCodes(): array
    {
        return []; // hardcoded for open source
        try {
            $request = new GetAllCurrencyCodesRequest();

            $response = $this->bridgeService->getAllCurrencyCodes($request);
        } catch (Exception $e) {
            $this->logger->critical("Exception while getting all the currency codes", [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return [];
        }

        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException("Could not get the currency codes: {$response->getMessage()}");
        }

        return $response->getCurrencyCodes();
    }

    /**
     * Get system-wide active tax-percentage
     *
     * @return float
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getTaxPercentage(): float
    {
        return 19; // hardcoded for open source

        try {
            $request  = new GetTaxPercentageRequest();
            $response = $this->bridgeService->getTaxPercentage($request);
        } catch (Exception $e) {
            $this->logger->critical("Exception while getting the tax percentage", [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return false;
        }

        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException("Could not get the tax percentage: {$response->getMessage()}");
        }

        return $response->getTaxPercentage();
    }

    /**
     * Get the transaction min & max payment data for each supported payment tool
     *
     * @return GetMinMaxTransactionDataResponse
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getMinMaxTransactionData(): GetMinMaxTransactionDataResponse
    {
        try {
            $request  = new GetMinMaxTransactionDataRequest();
            $response = $this->bridgeService->getMinMaxTransactionData($request);
        } catch (Exception $e) {
            $msg = "Exception while getting min & max transaction data";
            $this->logger->critical($msg, [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            throw new FinancesHubBridgeException($msg . " | {$e->getMessage()}");
        }

        if (!$response->isSuccess()) {
            throw new FinancesHubBridgeException("Could not get min max transaction data: {$response->getMessage()}");
        }

        return $response;
    }

    /**
     * @param float  $price
     * @param string $currency
     *
     * @return string
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getStripePaymentIntentToken(float $price, string $currency): string
    {
        try {
            $request  = new CreatePaymentIntentRequest();

            $request->setPrice($price);
            $request->setCurrencyCode($currency);

            $response = $this->bridgeService->createStripePaymentIntent($request);
            if (!$response->isSuccess()) {
                $msg = "Got non-successful response when calling for Stripe PaymentIntent token. Msg: {$response->getMessage()}, code: {$response->getCode()}";
                throw new FinancesHubBridgeException($msg);
            }
        } catch (Exception|TypeError $e) {
            $msg = "Exception while getting Stripe PaymentIntent token";
            $this->logger->critical($msg, [
                "msg"   => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            throw new FinancesHubBridgeException($msg . " | {$e->getMessage()}");
        }

        return $response->getToken();
    }

    /**
     * Will look over the transaction based products,
     * if transaction contains data of given product then matching {@see EntityProduct} will be returned
     * otherwise exception is thrown
     *
     * @param Transaction   $transaction
     * @param EntityProduct $entityProduct
     *
     * @return BridgeProduct
     */
    public static function getProductEntityFromTransaction(Transaction $transaction, EntityProduct $entityProduct): BridgeProduct
    {
        foreach ($transaction->getProducts() as $transactionProduct) {
            if ($transactionProduct->getId() === $entityProduct->getId()) {
                return $transactionProduct;
            }
        }

        throw new LogicException("Transaction does not contain this product! Product id: {$entityProduct->getId()}");
    }

}