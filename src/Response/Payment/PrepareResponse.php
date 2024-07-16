<?php

namespace App\Response\Payment;

use App\Action\Payment\PreparedPaymentAction;
use App\Response\Base\BaseResponse;

/**
 * {@see PreparedPaymentAction::}
 */
class PrepareResponse extends BaseResponse
{
    private int $orderId;

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }
}