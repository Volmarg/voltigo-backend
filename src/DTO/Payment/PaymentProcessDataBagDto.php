<?php

namespace App\DTO\Payment;

use FinancesHubBridge\Enum\PaymentToolEnum;
use LogicException;

/**
 * This dto contains data needed for the whole payment process
 */
class PaymentProcessDataBagDto
{
    private int $productId;
    private int $quantity;
    private string $currencyCode;
    private float $calculatedUnitPrice;
    private float $calculatedUnitPriceWithTax;
    private array $paymentToolData = [];
    private PaymentToolEnum $paymentTool;

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     */
    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @return float
     */
    public function getCalculatedUnitPrice(): float
    {
        return $this->calculatedUnitPrice;
    }

    /**
     * @param float $calculatedUnitPrice
     */
    public function setCalculatedUnitPrice(float $calculatedUnitPrice): void
    {
        $this->calculatedUnitPrice = $calculatedUnitPrice;
    }

    /**
     * @return float
     */
    public function getCalculatedUnitPriceWithTax(): float
    {
        return $this->calculatedUnitPriceWithTax;
    }

    /**
     * @param float $calculatedUnitPriceWithTax
     */
    public function setCalculatedUnitPriceWithTax(float $calculatedUnitPriceWithTax): void
    {
        $this->calculatedUnitPriceWithTax = $calculatedUnitPriceWithTax;
    }

    /**
     * @return array
     */
    public function getPaymentToolData(): array
    {
        return $this->paymentToolData;
    }

    /**
     * @param array $paymentToolData
     */
    public function setPaymentToolData(array $paymentToolData): void
    {
        $this->paymentToolData = $paymentToolData;
    }

    /**
     * @return PaymentToolEnum
     */
    public function getPaymentTool(): PaymentToolEnum
    {
        return $this->paymentTool;
    }

    /**
     * @param PaymentToolEnum $paymentTool
     */
    public function setPaymentTool(PaymentToolEnum $paymentTool): void
    {
        $this->paymentTool = $paymentTool;
    }

    /**
     * @param array $dataArray
     *
     * @return PaymentProcessDataBagDto
     */
    public static function fromDataArray(array $dataArray): PaymentProcessDataBagDto
    {
        $productId                  = $dataArray['productId'] ?? 0;
        $quantity                   = $dataArray['quantity'] ?? 0;
        $currencyCode               = $dataArray['currencyCode'] ?? "";
        $calculatedUnitPrice        = $dataArray['calculatedUnitPrice'] ?? 0;
        $calculatedUnitPriceWithTax = $dataArray['calculatedUnitPriceWithTax'] ?? 0;
        $paymentToolData            = $dataArray['paymentToolData'] ?? []; // that's ok - tool might need no data at all
        $paymentToolName            = $dataArray['paymentTool'] ?? "";

        $paymentToolEnum = PaymentToolEnum::tryFrom($paymentToolName);
        if (empty($paymentToolEnum)) {
            throw new LogicException("This payment tool is not supported: {$paymentToolName}");
        }

        $dto = new PaymentProcessDataBagDto();
        $dto->setProductId($productId);
        $dto->setQuantity($quantity);
        $dto->setCurrencyCode($currencyCode);
        $dto->setCalculatedUnitPrice($calculatedUnitPrice);
        $dto->setCalculatedUnitPriceWithTax($calculatedUnitPriceWithTax);
        $dto->setPaymentToolData($paymentToolData);
        $dto->setPaymentTool($paymentToolEnum);

        return $dto;
    }
}