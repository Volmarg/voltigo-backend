<?php

namespace App\Enum\Payment;

use App\Action\Payment\PaymentAction;

/**
 * States used for tracking on which state the payment process is currently at.
 * This is explicitly used in {@see PaymentAction::pay()} to keep tracking what was done so far before exception
 * was thrown etc.
 */
enum PaymentProcessStateEnum
{
    case BEGINNING;
    case CREATED_FINANCES_HUB_TRANSACTION;
    case CREATED_SNAPSHOTS_AND_ORDER;
    case REAL_PAYMENT_BEGAN_DATA_SENT_TO_FINANCES_HUB;
    case GOT_RESPONSE_FROM_FINANCES_HUB;
}
