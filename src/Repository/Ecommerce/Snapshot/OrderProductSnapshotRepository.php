<?php

namespace App\Repository\Ecommerce\Snapshot;

use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderProductSnapshot|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderProductSnapshot|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderProductSnapshot[]    findAll()
 * @method OrderProductSnapshot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<OrderProductSnapshot>
 */
class OrderProductSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderProductSnapshot::class);
    }
}
