<?php

namespace App\Repository\Email;

use App\Entity\Email\EmailAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailAttachment[]    findAll()
 * @method EmailAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailAttachment::class);
    }

    /**
     * Remove entities older than `x` hours
     *
     * @param int $hoursCount
     * @return EmailAttachment[]
     */
    public function getEntitiesForCleanUp(int $hoursCount): array
    {
        $beforeDate = (new \DateTime())->modify("-{$hoursCount} HOURS")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("ea")
            ->from(EmailAttachment::class, "ea")
            ->where("ea.created < :beforeDate")
            ->setParameter("beforeDate", $beforeDate);

        return $queryBuilder->getQuery()->execute();
    }
}

