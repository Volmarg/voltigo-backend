<?php

namespace App\Repository\Job;

use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method JobSearchResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobSearchResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobSearchResult[]    findAll()
 * @method JobSearchResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<JobSearchResult>
 */
class JobSearchResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobSearchResult::class);
    }

    /**
     * Will return all the matching {@see JobSearchResult} for {@see User}
     *
     * @param User $user
     *
     * @return JobSearchResult[]
     */
    public function findForUser(User $user): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("jsr")
            ->from(JobSearchResult::class, "jsr")
            ->where("jsr.user = :user")
            ->setParameter("user", $user)
            ->orderBy("jsr.created", "DESC");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return entities which can be removed
     *
     * @param int $hoursCount
     * @return int
     */
    public function removeOlderThanHours(int $hoursCount): int
    {
        $beforeDate = (new DateTime())->modify("-{$hoursCount} HOURS")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->delete(JobSearchResult::class, "e")
            ->where("e.created < :beforeDate")
            ->setParameter("beforeDate", $beforeDate);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $status
     * @param int    $hoursOffset
     *
     * @return JobSearchResult[]
     */
    public function findAllOlderThanWithStatus(string $status, int $hoursOffset): array
    {
        $minDate = (new DateTime())->modify("-{$hoursOffset} HOURS");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jsr")
            ->from(JobSearchResult::class, "jsr")
            ->where("jsr.created < :minDate")
            ->andWhere("jsr.status = :status")
            ->setParameters([
                'status'  => $status,
                'minDate' => $minDate,
            ]);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Returns the entries which should be refunded to user.
     *
     * @return JobSearchResult[]
     */
    public function findAllRefundAble($excludeWithReturnedPoints = true): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jsr")
            ->from(JobSearchResult::class, "jsr")
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            "jsr.status", ":doneStatuses"
                        ),
                        $queryBuilder->expr()->lt(
                            "jsr.percentageDone",
                            100
                        )
                    ),
                    $queryBuilder->expr()->eq("jsr.status", ":errorStatus")
                )
            )
            ->setParameters([
                "doneStatuses" => [
                    SearchResultStatusEnum::DONE->name,
                    SearchResultStatusEnum::PARTIALY_DONE->name,
                ],
                "errorStatus" => SearchResultStatusEnum::ERROR->name
            ]);

        if ($excludeWithReturnedPoints) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->isNull("jsr.returnedPointsHistory")
            );
        }

        return $queryBuilder->getQuery()->execute();
    }
}
