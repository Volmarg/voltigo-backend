<?php

namespace App\Repository\Ecommerce\Product;

use App\Entity\Ecommerce\Product\PointProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PointProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method PointProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method PointProduct[]    findAll()
 * @method PointProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<PointProduct>
 */
class PointProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointProduct::class);
    }

    /**
     * @return PointProduct[]
     */
    public function findAllAccessible(): array
    {
        return $this->findBy([
            "deleted" => false,
            "active"  => true,
        ],[
            'amount' => "ASC"
        ]);
    }
}
