<?php

namespace App\Repository\Storage;

use App\Entity\Storage\OneTimeJwtTokenStorage;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OneTimeJwtTokenStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method OneTimeJwtTokenStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method OneTimeJwtTokenStorage[]    findAll()
 * @method OneTimeJwtTokenStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OneTimeJwtTokenStorageRepository extends AbstractStorageRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OneTimeJwtTokenStorage::class);
    }

    /**
     * Will find one token by the token in
     *
     * @param string $token
     * @return OneTimeJwtTokenStorage|null
     */
    public function findByToken(string $token): ?OneTimeJwtTokenStorage
    {
        return $this->findOneBy([
            OneTimeJwtTokenStorage::FIELD_NAME_TOKEN => $token,
        ]);
    }

    /**
     * Will remove the tokens which are already expired anyway
     */
    public function removeOldEntries(): int
    {
        $nowTimestamp = (new DateTime())->getTimestamp();
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->delete(OneTimeJwtTokenStorage::class, "jwt")
            ->where("jwt.tokenExpirationTimestamp < :nowTimestamp")
            ->setParameter("nowTimestamp", $nowTimestamp);

        return $queryBuilder->getQuery()->execute();
    }

}
