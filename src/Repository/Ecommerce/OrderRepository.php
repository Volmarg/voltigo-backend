<?php

namespace App\Repository\Ecommerce;

use App\Entity\Ecommerce\Order;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FinancesHubBridge\Enum\PaymentStatusEnum;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @param int $olderThanHours
     *
     * @return Order[]
     */
    public function getStuckOrders(int $olderThanHours): array
    {
        $minDate = (new DateTime())->modify("-{$olderThanHours} HOUR")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("o")
            ->from(Order::class, "o")
            ->where("o.status IN (:statuses)")
            ->andWhere("o.transferredToFinancesHub = 0")
            ->andWhere("o.created < :minDate")
            ->setParameter("minDate", $minDate)
            ->setParameter("statuses", [Order::STATUS_PREPARED, PaymentStatusEnum::PENDING->name]);

        return $queryBuilder->getQuery()->execute();
    }

}
