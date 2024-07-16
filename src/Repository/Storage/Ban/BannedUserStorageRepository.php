<?php

namespace App\Repository\Storage\Ban;

use App\Entity\Security\User;
use App\Entity\Storage\Ban\BannedUserStorage;
use App\Repository\Storage\AbstractStorageRepository;
use App\Repository\Storage\Ban\FindLatestValid\QueryHandler\GetLatestBannedEntryTrait;
use App\Repository\Storage\Ban\FindLatestValid\QueryModifier\AddWhereValidTrait;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BannedUserStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method BannedUserStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method BannedUserStorage[]    findAll()
 * @method BannedUserStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannedUserStorageRepository extends AbstractStorageRepository
{
    use AddWhereValidTrait;
    use GetLatestBannedEntryTrait;

    public function __construct(
        ManagerRegistry $registry,
    )
    {
        parent::__construct($registry, BannedUserStorage::class);
    }

    /**
     * @param User $user
     * @param int  $validTillMinutesOffset
     *
     * @return BannedUserStorage|null
     */
    public function findLatestValid(User $user, int $validTillMinutesOffset): ?BannedUserStorage
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("bus")
                     ->from(BannedUserStorage::class, "bus")
                     ->where("bus.user = :user")
                     ->setParameter("user", $user)
                     ->orderBy("bus.validTill", "DESC");

        $queryBuilder = $this->addWhereValid($queryBuilder, "bus");

        /** @var BannedUserStorage | null $result */
        $result = $this->getLatestBannedEntry($queryBuilder, $validTillMinutesOffset);

        return $result;
    }
}
