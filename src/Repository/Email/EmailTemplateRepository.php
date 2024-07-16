<?php

namespace App\Repository\Email;

use App\Entity\Email\EmailTemplate;
use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailTemplate[]    findAll()
 * @method EmailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * Will return all not deleted templates for user
     *
     * @param User $user
     * @return array
     */
    public function getAll(User $user): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("et")
            ->from(EmailTemplate::class, "et")
            ->where("et.user = :userId")
            ->andWhere("et.deleted = 0")
            ->setParameter("userId", $user->getId());

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return all the clone-able templates
     *
     * @return array
     */
    public function getAllCloneAble(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("et")
            ->from(EmailTemplate::class, "et")
            ->where("et.user IS NULL")
            ->andWhere("et.deleted = 0");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will update existing template or create new one
     *
     * @param EmailTemplate $emailTemplate
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EmailTemplate $emailTemplate): void
    {
        $this->_em->persist($emailTemplate);
        $this->_em->flush();
    }

    /**
     * Will delete entity for given id
     *
     * @param int  $id
     * @param User $user
     * @param bool $canHandlePredefined
     */
    public function softDeleteById(int $id, User $user, bool $canHandlePredefined): void
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->update(EmailTemplate::class, "emt")
            ->set("emt.deleted", 1)
            ->where("emt.id = :id")
            ->setParameter("id", $id);

        if (!$canHandlePredefined) {
            $queryBuilder->andWhere("emt.user = :user");
        } else {
            // null means that user is super-user but can delete only his own templates or the predefined ones
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    "emt.user IS NULL",
                    "emt.user = :user"
                )
            );
        }

        $queryBuilder->setParameter('user', $user);
        $queryBuilder->getQuery()->execute();
    }
}
