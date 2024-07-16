<?php

namespace App\Repository\Storage;

use App\Entity\Storage\ApiStorage;
use App\Entity\Storage\BannedJwtTokenStorage;
use App\Entity\Storage\CsrfTokenStorage;
use App\Entity\Storage\FrontendErrorStorage;
use App\Entity\Storage\OneTimeJwtTokenStorage;
use App\Entity\Storage\PageTrackingStorage;
use App\Entity\Storage\StorageInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * General class which contains the most common logic related to storages
 * @extends ServiceEntityRepository<ApiStorage | BannedJwtTokenStorage | CsrfTokenStorage | FrontendErrorStorage | OneTimeJwtTokenStorage | PageTrackingStorage>
 */
abstract class AbstractStorageRepository extends ServiceEntityRepository
{

    /**
     * @var string $entityFullNamespace
     */
    private string $entityFullNamespace;

    /**
     * @param ManagerRegistry $registry
     * @param string $entityFullNamespace
     */
    public function __construct(ManagerRegistry $registry, string $entityFullNamespace)
    {
        $this->entityFullNamespace = $entityFullNamespace;
        parent::__construct($registry, $entityFullNamespace);
    }

    /**
     * Will save the storage entry (create or update the existing one)
     *
     * @param StorageInterface $storageEntity
     */
    public function save(StorageInterface $storageEntity): void
    {
        $this->_em->persist($storageEntity);
        $this->_em->flush();
    }

    /**
     * Remove entities older than `x` hours
     *
     * @param int $hoursCount
     * @return int
     */
    public function removeOlderThanHours(int $hoursCount): int
    {
        $beforeDate = (new \DateTime())->modify("-{$hoursCount} HOURS")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->delete($this->entityFullNamespace, "e")
            ->where("e.created < :beforeDate")
            ->setParameter("beforeDate", $beforeDate);

        return $queryBuilder->getQuery()->execute();
    }

}