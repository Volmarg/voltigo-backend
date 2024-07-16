<?php

namespace App\DTO\Order;

use App\DTO\Payment\PaymentProcessDataBagDto;

use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\PaymentProcessData;
use App\Entity\Ecommerce\Product\PointProduct;
use FinancesHubBridge\Dto\Product;
use FinancesHubBridge\Dto\Transaction;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Dto used to transfer all the data created in order preparation process
 */
class PreparedOrderBagDto
{
    private ?PaymentProcessDataBagDto $paymentProcessDataBagDto = null;
    private ?JsonResponse $response = null;
    private Product|PointProduct|null $product = null;
    private ?Transaction $transaction = null;
    private ?Order $order = null;
    private ?PaymentProcessData $paymentProcessData = null;

    public function getPaymentProcessDataBagDto(): ?PaymentProcessDataBagDto
    {
        return $this->paymentProcessDataBagDto;
    }

    public function setPaymentProcessDataBagDto(?PaymentProcessDataBagDto $paymentProcessDataBagDto): void
    {
        $this->paymentProcessDataBagDto = $paymentProcessDataBagDto;
    }

    public function getResponse(): ?JsonResponse
    {
        return $this->response;
    }

    public function setResponse(?JsonResponse $response): void
    {
        $this->response = $response;
    }

    public function getProduct(): PointProduct|Product|null
    {
        return $this->product;
    }

    public function setProduct(PointProduct|Product|null $product): void
    {
        $this->product = $product;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function isTransactionSet(): bool
    {
        return !is_null($this->getTransaction());
    }

    public function isResponseSet(): bool
    {
        return !is_null($this->getResponse());
    }

    public function getPaymentProcessData(): ?PaymentProcessData
    {
        return $this->paymentProcessData;
    }

    public function setPaymentProcessData(?PaymentProcessData $paymentProcessData): void
    {
        $this->paymentProcessData = $paymentProcessData;
    }

}