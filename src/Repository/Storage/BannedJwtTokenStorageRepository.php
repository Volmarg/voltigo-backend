<?php

namespace App\Repository\Storage;

use App\Entity\Storage\BannedJwtTokenStorage;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BannedJwtTokenStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method BannedJwtTokenStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method BannedJwtTokenStorage[]    findAll()
 * @method BannedJwtTokenStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannedJwtTokenStorageRepository extends AbstractStorageRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannedJwtTokenStorage::class);
    }

    /**
     * Will find one token by the token in
     *
     * @param string $token
     * @return BannedJwtTokenStorage|null
     */
    public function findByToken(string $token): ?BannedJwtTokenStorage
    {
        return $this->findOneBy([
            BannedJwtTokenStorage::FIELD_NAME_TOKEN => $token,
        ]);
    }

    /**
     * Will remove the tokens which are already expired anyway
     */
    public function removeOldEntries(): int
    {
        $nowTimestamp = (new DateTime())->getTimestamp();
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->delete(BannedJwtTokenStorage::class, "jwt")
            ->where("jwt.tokenExpirationTimestamp < :nowTimestamp")
            ->setParameter("nowTimestamp", $nowTimestamp);

        return $queryBuilder->getQuery()->execute();
    }

}
