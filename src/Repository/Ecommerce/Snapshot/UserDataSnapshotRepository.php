<?php

namespace App\Repository\Ecommerce\Snapshot;

use App\Entity\Ecommerce\Snapshot\UserDataSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserDataSnapshot|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDataSnapshot|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDataSnapshot[]    findAll()
 * @method UserDataSnapshot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<UserDataSnapshot>
 */
class UserDataSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDataSnapshot::class);
    }
}
