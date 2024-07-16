<?php

namespace App\DTO\Api\FinancesHub;

use FinancesHubBridge\Enum\PaymentStatusEnum;

/**
 * Contains all the necessary information for handling the transaction (incoming data) for order (internal),
 * Provides also some useful information that can be displayed to user in E-Mail etc.
 */
class TransactionDetailDTO
{

    /**
     * @var string $paymentToolName
     */
    private string $paymentToolName;

    /**
     * @var string $paymentToolContactPage
     */
    private string $paymentToolContactPage;

    /**
     * Might be null if the payment tool is not even able to trigger the payment
     * @var string|null $paymentIdentifier
     */
    private ?string $paymentIdentifier = null;

    /**
     * Any kind of tokens, literally anything that was used for the requests,
     * this will be added to user E-Mail so must have as much human read-able form as possible
     *
     * @var string
     */
    private string $requestData = "";

    /**
     * @var int $orderId
     */
    private int $orderId;

    /**
     * @var string $status
     */
    private string $status;

    /**
     * @var float $moneyAcknowledged
     */
    private float $moneyAcknowledged;

    /**
     * @var int $transactionId
     */
    private int $transactionId;

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     */
    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isTransactionSuccessful(): bool
    {
        return (
                $this->getStatus() === PaymentStatusEnum::SUCCESS_NOT_EQUAL_DEMANDED->name
            ||  $this->getStatus() === PaymentStatusEnum::SUCCESS->name
        );
    }

    /**
     * @return bool
     */
    public function isTransactionSuccessfulAndEqualDemanded(): bool
    {
        return ($this->getStatus() === PaymentStatusEnum::SUCCESS->name);
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return float
     */
    public function getMoneyAcknowledged(): float
    {
        return $this->moneyAcknowledged;
    }

    /**
     * @param float $moneyAcknowledged
     */
    public function setMoneyAcknowledged(float $moneyAcknowledged): void
    {
        $this->moneyAcknowledged = $moneyAcknowledged;
    }

    /**
     * @return string
     */
    public function getPaymentToolName(): string
    {
        return $this->paymentToolName;
    }

    /**
     * @param string $paymentToolName
     */
    public function setPaymentToolName(string $paymentToolName): void
    {
        $this->paymentToolName = $paymentToolName;
    }

    /**
     * @return string|null
     */
    public function getPaymentIdentifier(): ?string
    {
        return $this->paymentIdentifier;
    }

    /**
     * @param string|null $paymentIdentifier
     */
    public function setPaymentIdentifier(?string $paymentIdentifier): void
    {
        $this->paymentIdentifier = $paymentIdentifier;
    }

    /**
     * @param array $data
     *
     * @return TransactionDetailDTO
     */
    public static function fromArray(array $data): TransactionDetailDTO
    {
        $dto = new TransactionDetailDTO();

        $orderId                = $data['orderId'];
        $status                 = $data['status'];
        $moneyAcknowledged      = $data['moneyAcknowledged'];
        $paymentToolName        = $data['paymentToolName'];
        $paymentIdentifier      = $data['paymentIdentifier'];
        $requestData            = $data['requestData'];
        $paymentToolContactPage = $data['paymentToolContactPage'];
        $transactionId          = $data['transactionId'];

        $dto->setStatus($status);
        $dto->setOrderId($orderId);
        $dto->setMoneyAcknowledged($moneyAcknowledged);
        $dto->setPaymentToolName($paymentToolName);
        $dto->setPaymentIdentifier($paymentIdentifier);
        $dto->setPaymentToolContactPage($paymentToolContactPage);
        $dto->setRequestData($requestData);
        $dto->setTransactionId($transactionId);

        return $dto;
    }

    /**
     * @return string
     */
    public function getRequestData(): string
    {
        return $this->requestData;
    }

    /**
     * @param string $requestData
     */
    public function setRequestData(string $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @return string
     */
    public function getPaymentToolContactPage(): string
    {
        return $this->paymentToolContactPage;
    }

    /**
     * @param string $paymentToolContactPage
     */
    public function setPaymentToolContactPage(string $paymentToolContactPage): void
    {
        $this->paymentToolContactPage = $paymentToolContactPage;
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * @param int $transactionId
     */
    public function setTransactionId(int $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

}