<?php

namespace App\Repository\Regulation;

use App\Entity\Regulation\RegulationData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RegulationData|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegulationData|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegulationData[]    findAll()
 * @method RegulationData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegulationDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationData::class);
    }

}
