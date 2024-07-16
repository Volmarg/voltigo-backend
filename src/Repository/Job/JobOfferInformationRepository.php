<?php

namespace App\Repository\Job;

use App\Entity\Job\JobOfferInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method JobOfferInformation|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobOfferInformation|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobOfferInformation[]    findAll()
 * @method JobOfferInformation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<JobOfferInformation>
 */
class JobOfferInformationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOfferInformation::class);
    }

    /**
     * Will return entities which can be removed
     *
     * @param int $hoursCount
     * @return int
     */
    public function removeOlderThanHours(int $hoursCount): int
    {
        $beforeDate   = (new \DateTime())->modify("-{$hoursCount} HOURS")->format("Y-m-d H:i:s");
        $queryBuilder = $this->_em->createQueryBuilder();

        // join ain't working with delete thus iterating
        $queryBuilder->select("joi")
            ->from(JobOfferInformation::class, "joi")
            ->leftJoin('joi.jobApplications', "joa")
            ->where("joi.created < :beforeDate")
            ->andWhere("joa.id IS NULL")
            ->setParameter("beforeDate", $beforeDate);

        $countRemovedEntries = 0;
        $results             = $queryBuilder->getQuery()->execute();

        foreach ($results as $result) {
            $this->_em->remove($result);
            $countRemovedEntries++;
        }

        $this->_em->flush();

        return $countRemovedEntries;
    }
}
