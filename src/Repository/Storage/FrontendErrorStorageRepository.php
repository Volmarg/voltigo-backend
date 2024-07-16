<?php

namespace App\Repository\Storage;

use App\Entity\Storage\FrontendErrorStorage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FrontendErrorStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FrontendErrorStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FrontendErrorStorage[]    findAll()
 * @method FrontendErrorStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FrontendErrorStorageRepository extends AbstractStorageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrontendErrorStorage::class);
    }

}
