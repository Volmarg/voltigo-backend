<?php

namespace App\Repository\Ecommerce;

use App\Entity\Ecommerce\PointShopProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PointShopProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method PointShopProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method PointShopProduct[]    findAll()
 * @method PointShopProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PointShopProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointShopProduct::class);
    }

    /**
     * @param string $identifier
     *
     * @return PointShopProduct|null
     */
    public function findByInternalIdentifier(string $identifier): ?PointShopProduct
    {
        return $this->findOneBy([
            "internalIdentifier" => $identifier,
        ]);
    }
}
