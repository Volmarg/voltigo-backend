<?php

namespace App\Exception\Payment;

use App\Enum\Payment\PaymentProcessStateEnum;
use App\Service\Payment\PaymentService;
use Exception;

/**
 * Indicates that something is wrong with payment preparation. For example {@see PaymentService::prepareFromRequestData()}
 */
class PaymentPrepareException extends Exception
{
    private PaymentProcessStateEnum $paymentProcessState;

    public function getPaymentProcessState(): PaymentProcessStateEnum
    {
        return $this->paymentProcessState;
    }

    public function setPaymentProcessState(PaymentProcessStateEnum $paymentProcessState): void
    {
        $this->paymentProcessState = $paymentProcessState;
    }

}