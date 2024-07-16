<?php

namespace App\Repository\Storage;

use App\Entity\Security\User;
use App\Entity\Storage\PageTrackingStorage;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageTrackingStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageTrackingStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageTrackingStorage[]    findAll()
 * @method PageTrackingStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageTrackingStorageRepository extends AbstractStorageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageTrackingStorage::class);
    }

    /**
     * Will return count of calls for given ip in last X minutes
     *
     * @param int         $minutesOffset
     * @param string      $ip
     * @param string|null $targetRoute
     *
     * @return int
     */
    public function getRecentCallCountForIp(int $minutesOffset, string $ip, ?string $targetRoute = null): int
    {
        $afterDate    = (new DateTime())->modify("-{$minutesOffset} MINUTES")->format("Y-m-d H:i:s");
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("COUNT(pts.id) AS callCount")
            ->from(PageTrackingStorage::class, "pts")
            ->where("pts.created >= :afterDate")
            ->andWhere("pts.ip = :ip")
            ->setParameter("afterDate", $afterDate)
            ->setParameter("ip", $ip);

        if (!empty($targetRoute)) {
            $queryBuilder->andWhere("pts.routeName = :targetRoute")
                ->setParameter("targetRoute", $targetRoute);
        }

        $count = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SINGLE_SCALAR)->execute();

        return $count;
    }

    /**
     * Will return of count of calls for given user in last X minutes
     *
     * @param int  $minutesOffset
     * @param User $user
     *
     * @return int
     */
    public function getRecentCallCountForUser(int $minutesOffset, User $user): int
    {
        $afterDate    = (new DateTime())->modify("-{$minutesOffset} MINUTES")->format("Y-m-d H:i:s");
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("
                COUNT(pts.id) AS callCount
            ")
             ->from(PageTrackingStorage::class, "pts")
             ->where("pts.created >= :afterDate")
             ->andWhere("pts.user = :user")
             ->setParameter("afterDate", $afterDate)
             ->setParameter("user", $user);

        $count = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SINGLE_SCALAR)->execute();

        return $count;
    }

    /**
     * Yes. This is nasty, but there seems to be some problem in the entity encryption external package.
     * Even tho there is nothing encoded on the {@see PageTrackingStorage} the encryption is still
     * being called for the entity update.
     *
     * Now problem is that, the {@see PageTrackingStorage} is updated on EACH request so this was slowing
     * down the project a lot, simple page loading had +1.5s in some places.
     *
     * Using the query builder speeds loading for bunch (if not all) pages.
     *
     * @param array $propsWithValues
     * @param int   $id
     */
    public function updateField(array $propsWithValues, int $id): void
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->update(PageTrackingStorage::class, "pts")
            ->where("pts.id = :id")
            ->setParameter("id", $id);

        foreach ($propsWithValues as $entityPropName => $value) {
            $paramName = "{$entityPropName}_value";
            $queryBuilder->set("pts.{$entityPropName}", ":$paramName")
                         ->setParameter($paramName, $value);
        }

        if (!in_array('modified', $propsWithValues)) {
            $queryBuilder->set("pts.modified", ":modified")
                         ->setParameter("modified", new DateTime());
        }

        $queryBuilder->getQuery()->execute();
    }
}
