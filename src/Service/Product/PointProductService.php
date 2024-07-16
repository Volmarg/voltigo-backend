<?php

namespace App\Service\Product;

use App\DTO\Api\FinancesHub\TransactionDetailDTO;
use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Entity\Ecommerce\Snapshot\Product\OrderPointProductSnapshot;
use App\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles all the calculation / grants etc. for the point based products:
 * - {@see OrderPointProductSnapshot}
 * - {@see PointProduct}
 */
class PointProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {

    }

    /**
     * Will calculate the points that user will get from the transaction
     *
     * @throws NotFoundException
     */
    public function calculateGrantedPoints(TransactionDetailDTO $transactionDetail): int
    {
        $order = $this->entityManager->find(Order::class, $transactionDetail->getOrderId());
        if (empty($order)) {
            throw new NotFoundException("No order was found for id:{$transactionDetail->getOrderId()}");
        }

        if ($transactionDetail->isTransactionSuccessfulAndEqualDemanded()) {
            return $order->getExpectedPoints();
        }

        $pricePerPoint    = $order->getExpectedPoints() / $order->getCost()->getTotalWithTax();
        $calculatedPoints = (int)$pricePerPoint * $transactionDetail->getMoneyAcknowledged();

        return $calculatedPoints;
    }
}