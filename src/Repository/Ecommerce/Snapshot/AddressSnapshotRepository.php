<?php

namespace App\Repository\Ecommerce\Snapshot;

use App\Entity\Ecommerce\Snapshot\AddressSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AddressSnapshot|null find($id, $lockMode = null, $lockVersion = null)
 * @method AddressSnapshot|null findOneBy(array $criteria, array $orderBy = null)
 * @method AddressSnapshot[]    findAll()
 * @method AddressSnapshot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<AddressSnapshot>
 */
class AddressSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AddressSnapshot::class);
    }

}
