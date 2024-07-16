<?php

namespace App\Repository\Ecommerce\User;

use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserPointHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPointHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPointHistory[]    findAll()
 * @method UserPointHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPointHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPointHistory::class);
    }

    /**
     * @param User $user
     *
     * @return UserPointHistory[]
     */
    public function findAllForUser(User $user): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("uph")
            ->from(UserPointHistory::class, "uph")
            ->where(
                $queryBuilder->expr()->eq("uph.user", ":user")
            )->setParameter("user", $user)
            ->orderBy("uph.created", "DESC");

        return $queryBuilder->getQuery()->execute();
    }
}
