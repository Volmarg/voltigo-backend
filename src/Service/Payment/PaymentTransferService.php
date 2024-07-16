<?php

namespace App\Service\Payment;

use App\Action\Payment\PaymentAction;
use App\DTO\Payment\PaymentProcessDataBagDto;
use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\Product\Product;
use App\Entity\Ecommerce\Product\Product as ProductEntity;
use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use App\Exception\Payment\PriceCalculationException;
use App\Service\Api\FinancesHub\FinancesHubService;
use FinancesHubBridge\Dto\Transaction;
use FinancesHubBridge\Enum\PaymentToolEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;

/**
 * If for whatever reason {@see Order} will not get transferred to {@see FinancesHubService} via standard measures:
 * - {@see PaymentAction}
 *
 * then this class is the place to store reusable logic for such cases.
 * The logic in here should allow transferring the orders that for some reason were not send to {@see FinancesHubService}.
 */
class PaymentTransferService
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * Will build the payment bag for order already existing in db.
     * Can be used to transfer the order to {@see FinancesHubService}
     *
     * @param Order                $order
     * @param OrderProductSnapshot $productSnapshot
     * @param ProductEntity        $product
     *
     * @return PaymentProcessDataBagDto
     */
    public function buildPaymentBag(
        Order                $order,
        OrderProductSnapshot $productSnapshot,
        ProductEntity        $product
    ): PaymentProcessDataBagDto
    {
        $paymentBag = new PaymentProcessDataBagDto();
        $paymentBag->setPaymentTool(PaymentToolEnum::tryFrom($order->getPaymentProcessData()->getPaymentTool()));
        $paymentBag->setPaymentToolData($order->getPaymentProcessData()->getPaymentToolData());
        $paymentBag->setCurrencyCode($order->getTargetCurrencyCode());
        $paymentBag->setQuantity($productSnapshot->getQuantity());
        $paymentBag->setCalculatedUnitPrice($order->getPaymentProcessData()->getTargetCurrencyCalculatedUnitPrice());
        $paymentBag->setCalculatedUnitPriceWithTax($order->getPaymentProcessData()->getTargetCurrencyCalculatedUnitPriceWithTax());
        $paymentBag->setProductId($product->getId());

        return $paymentBag;
    }

    /**
     * @param Product                  $productFromSnapshot
     * @param OrderProductSnapshot     $productSnapshot
     * @param PaymentProcessDataBagDto $paymentBag
     * @param Order                    $order
     *
     * @return Transaction
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws PriceCalculationException
     */
    public function buildTransaction(
        Product                  $productFromSnapshot,
        OrderProductSnapshot     $productSnapshot,
        PaymentProcessDataBagDto $paymentBag,
        Order                    $order
    ): Transaction {
        $transaction = $this->paymentService->createTransaction($productFromSnapshot, $paymentBag, $order->getUser());

        $hasMatch = false;
        foreach ($transaction->getProducts() as $product) {
            if ($product->getId() === $productFromSnapshot->getId()) {
                $product->setProductSnapshotId($productSnapshot->getId());
                $hasMatch = true;
                break;
            }
        }

        if (!$hasMatch) {
            throw new LogicException("Got no matching product inside transaction for product of id: {$productFromSnapshot->getId()}");
        }

        $transaction->setOrderId($order->getId());
        $transaction->setExpectedPriceWithTax($order->getCost()->getTotalWithTax());
        $transaction->setExpectedPriceWithoutTax($order->getCost()->getTotalWithoutTax());

        return $transaction;
    }

}