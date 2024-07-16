<?php

namespace App\Response\Payment\Stripe;

use App\Action\Payment\Stripe\StripePaymentAction;
use App\Response\Base\BaseResponse;

/**
 * {@see StripePaymentAction::getPaymentIntentToken()}
 */
class GetPaymentIntentToken extends BaseResponse
{
    private string $intentToken;

    public function getIntentToken(): string
    {
        return $this->intentToken;
    }

    public function setIntentToken(string $intentToken): void
    {
        $this->intentToken = $intentToken;
    }

}