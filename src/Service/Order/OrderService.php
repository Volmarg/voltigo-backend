<?php

namespace App\Service\Order;

use App\DTO\Order\OrderDataDto;
use App\DTO\Payment\PaymentProcessDataBagDto;
use App\Entity\Ecommerce\Cost\Cost;
use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\PaymentProcessData;
use App\Entity\Ecommerce\Product\Product as ProductEntity;
use App\Service\Api\FinancesHub\FinancesHubService;
use FinancesHubBridge\Dto\Product as FinancesHubBridgeProduct;
use App\Entity\Ecommerce\Snapshot\AddressSnapshot;
use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use App\Entity\Ecommerce\Snapshot\UserDataSnapshot;
use App\Entity\Security\User;
use App\Service\Payment\PriceCalculationService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Snapshot\SnapshotBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Dto\Transaction;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;

/**
 * Will create the snapshots based on the handled transaction.
 */
class OrderService
{
    public function __construct(
        private readonly PriceCalculationService  $priceCalculationService,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly EntityManagerInterface   $entityManager,
        private readonly SnapshotBuilderService   $snapshotBuilderService
    ) {
    }

    /**
     * @param ProductEntity            $product
     * @param Transaction              $transaction
     * @param PaymentProcessDataBagDto $paymentBag
     * @param PaymentProcessData       $paymentProcessData
     *
     * @return Order
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function saveOrderAndSnapshots(ProductEntity $product, Transaction $transaction, PaymentProcessDataBagDto $paymentBag, PaymentProcessData $paymentProcessData): Order
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if (empty($user->getAddress())) {
            throw new LogicException("This user is missing address data!");
        }

        $matchingTransactionProduct = FinancesHubService::getProductEntityFromTransaction($transaction, $product);

        /**
         * That's calculated again because the price is transaction is taken on user favour if something on front is wrong
         * {@see PriceCalculationService::PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE}
         */
        $productPriceWithTax = $this->priceCalculationService->getBilledBasePriceWithTax(
            $product,
            $product->getBaseCurrencyCode()
        );

        $orderProductSnapshot = $this->snapshotBuilderService->buildOrderProductSnapshot(
            $product,
            $matchingTransactionProduct->getTaxPercentage(),
            $matchingTransactionProduct->getQuantity(),
            $productPriceWithTax
        );

        $order           = $this->buildOrder($orderProductSnapshot, $paymentBag);
        $cost            = $this->buildCost($matchingTransactionProduct);
        $userSnapshot    = $this->snapshotBuilderService->buildUserSnapshot($user);
        $addressSnapshot = $this->snapshotBuilderService->buildAddressSnapshot($user->getAddress());

        $order->setPaymentProcessData($paymentProcessData);
        $this->bindSnapshotsAndSaveOrder(
            $order,
            $cost,
            $user,
            $userSnapshot,
            $addressSnapshot,
            $orderProductSnapshot,
        );

        // at this point the snapshot is persisted, so it will have its id
        $matchingTransactionProduct->setProductSnapshotId($orderProductSnapshot->getId());

        return $order;
    }

    /**
     * @param Order $order
     *
     * @return OrderDataDto
     */
    public function buildOrderDataDto(Order $order): OrderDataDto
    {
        $orderDataDto = new OrderDataDto();
        $orderDataDto->setCreated($order->getCreated()->format("Y-m-d H:i:s"));
        $orderDataDto->setId($order->getId());
        $orderDataDto->setStatus($order->getStatus());
        $orderDataDto->setTotalWithoutTax($order->getCost()->getTotalWithoutTax());
        $orderDataDto->setTotalWithTax($order->getCost()->getTotalWithTax());
        $orderDataDto->setTargetCurrencyCode($order->getTargetCurrencyCode());

        foreach ($order->getProductSnapshots() as $productSnapshot) {
            $productData = "{$productSnapshot->getName()} x {$productSnapshot->getQuantity()}";
            $orderDataDto->addProductData($productData);
        }

        return $orderDataDto;
    }

    /**
     * Binds all the snapshots & order related entities (relations) and save the whole order alongside
     * with the snapshots themselves
     *
     * @param Order                $order
     * @param Cost                 $cost
     * @param User                 $user
     * @param UserDataSnapshot     $userSnapshot
     * @param AddressSnapshot      $addressSnapshot
     * @param OrderProductSnapshot $orderProductSnapshot
     *
     * @throws Exception
     */
    private function bindSnapshotsAndSaveOrder(
        Order                $order,
        Cost                 $cost,
        User                 $user,
        UserDataSnapshot     $userSnapshot,
        AddressSnapshot      $addressSnapshot,
        OrderProductSnapshot $orderProductSnapshot,
    ): void
    {

        $this->entityManager->beginTransaction();
        try {
            $cost->setRelatedOrder($order);

            $addressSnapshot->setUser($userSnapshot);

            $order->setUserDataSnapshot($userSnapshot);
            $order->setUser($user);
            $order->setCost($cost);

            $userSnapshot->setAddressSnapshot($addressSnapshot);
            $userSnapshot->setRelatedOrder($order);
            $userSnapshot->setUser($user);

            $orderProductSnapshot->setOrder($order);

            $this->entityManager->persist($orderProductSnapshot);
            $this->entityManager->persist($order);
            $this->entityManager->persist($userSnapshot);
            $this->entityManager->persist($addressSnapshot);

            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        $this->entityManager->commit();
    }

    /**
     * @param FinancesHubBridgeProduct $matchingTransactionProduct
     *
     * @return Cost
     */
    private function buildCost(FinancesHubBridgeProduct $matchingTransactionProduct): Cost
    {
        $totalCostWithoutTax = $matchingTransactionProduct->getQuantity() * $matchingTransactionProduct->getCost();
        $totalCostWithTax    = $matchingTransactionProduct->getQuantity() * $matchingTransactionProduct->getCostWithTax();

        $cost = new Cost();
        $cost->setTotalWithTax($totalCostWithTax);
        $cost->setTotalWithoutTax($totalCostWithoutTax);
        $cost->setUsedTaxValue($matchingTransactionProduct->getTaxPercentage());
        $cost->setCurrencyCode($matchingTransactionProduct->getCurrency());

        return $cost;
    }

    /**
     * @param OrderProductSnapshot     $orderProductSnapshot
     * @param PaymentProcessDataBagDto $paymentBag
     *
     * @return Order
     */
    private function buildOrder(OrderProductSnapshot $orderProductSnapshot, PaymentProcessDataBagDto $paymentBag): Order
    {
        $order = new Order();
        $order->addProductSnapshot($orderProductSnapshot);
        $order->setTargetCurrencyCode($paymentBag->getCurrencyCode());
        $order->setStatus(PaymentStatusEnum::PENDING->name);

        return $order;
    }

}