<?php

namespace App\Repository\Security;

use App\Entity\Security\User;
use App\Entity\Security\UserRegulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserRegulation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRegulation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRegulation[]    findAll()
 * @method UserRegulation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRegulationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRegulation::class);
    }

    /**
     * Check if regulation of given identifier was accepted
     *
     * @param string $identifier
     * @param User   $user
     *
     * @return bool
     */
    public function isAccepted(string $identifier, User $user): bool
    {
        $regulation = $this->findOneBy([
            "identifier" => $identifier,
            'user'       => $user,
        ]);

        if (empty($regulation)) {
            return false;
        }

        return $regulation->isAccepted();
    }

}
