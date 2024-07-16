<?php

namespace App\Repository\Ecommerce;

use App\Entity\Ecommerce\PaymentProcessData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaymentProcessData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentProcessData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentProcessData[]    findAll()
 * @method PaymentProcessData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<PaymentProcessData>
 */
class PaymentProcessDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentProcessData::class);
    }
}
