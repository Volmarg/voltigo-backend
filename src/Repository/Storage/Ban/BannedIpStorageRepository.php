<?php

namespace App\Repository\Storage\Ban;

use App\Entity\Storage\Ban\BannedIpStorage;
use App\Repository\Storage\AbstractStorageRepository;
use App\Repository\Storage\Ban\FindLatestValid\QueryHandler\GetLatestBannedEntryTrait;
use App\Repository\Storage\Ban\FindLatestValid\QueryModifier\AddWhereValidTrait;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BannedIpStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method BannedIpStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method BannedIpStorage[]    findAll()
 * @method BannedIpStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannedIpStorageRepository extends AbstractStorageRepository
{
    use AddWhereValidTrait;
    use GetLatestBannedEntryTrait;

    public function __construct(
        ManagerRegistry $registry,
    )
    {
        parent::__construct($registry, BannedIpStorage::class);
    }

    /**
     * @param string $ip
     * @param int    $validTillMinutesOffset
     *
     * @return BannedIpStorage|null
     */
    public function findLatestValid(string $ip, int $validTillMinutesOffset): ?BannedIpStorage
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("bis")
            ->from(BannedIpStorage::class, "bis")
            ->where("bis.ip = :ip")
            ->setParameter("ip", $ip)
            ->orderBy("bis.validTill", "DESC");

        $queryBuilder = $this->addWhereValid($queryBuilder, "bis");

        /** @var BannedIpStorage | null $result */
        $result = $this->getLatestBannedEntry($queryBuilder, $validTillMinutesOffset);

        return $result;
    }

}
