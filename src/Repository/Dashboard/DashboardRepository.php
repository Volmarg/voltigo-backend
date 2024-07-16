<?php

namespace App\Repository\Dashboard;

use App\Entity\Job\JobApplication;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Service\Dashboard\DashboardDataProviderService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;

/**
 * {@see DashboardDataProviderService}
 */
class DashboardRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {

    }

    /**
     * @param User $user
     *
     * @return JobSearchResult[]
     */
    public function findPendingJobSearches(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select("js")
            ->from(JobSearchResult::class, "js")
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in("js.status", ":statuses"),
                    $qb->expr()->eq("js.user", ":user"),
                )
            )
            ->setParameter("statuses", [
                SearchResultStatusEnum::WIP->name,
                SearchResultStatusEnum::PENDING->name,
            ])
            ->setParameter("user", $user);

        return $qb->getQuery()->execute();
    }

    /**
     * @param User $user
     * @param int  $hoursOffset
     *
     * @return JobSearchResult[]
     */
    public function findRecentFailedJobSearches(User $user, int $hoursOffset): array
    {
        $olderThanDate = (new \DateTime())->modify("-{$hoursOffset} HOURS");
        $qb            = $this->entityManager->createQueryBuilder();

        $qb->select("js")
            ->from(JobSearchResult::class, "js")
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq("js.status", ":status"),
                    $qb->expr()->eq("js.user", ":user"),
                )
            )
            ->andWhere("js.modified > :olderThanDate")
            ->setParameter("status", SearchResultStatusEnum::PENDING->name)
            ->setParameter("olderThanDate", $olderThanDate)
            ->setParameter("user", $user);

        return $qb->getQuery()->execute();
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getCountOfApplicationPerDay(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        // CAST is used here because otherwise internally in mysql the DATE_FORMAT outputs string
        $qb->select("
            DATE_FORMAT(ja.created, '%Y') AS year,
            CAST(DATE_FORMAT(ja.created, '%c') AS UNSIGNED) AS month,
            CAST(DATE_FORMAT(ja.created, '%e') AS UNSIGNED) AS day,
            COUNT(ja.id) AS applicationCount
        ")
           ->from(JobApplication::class, "ja")
           ->where(
               $qb->expr()->eq("ja.user", ":user"),
           )
           ->setParameter("user", $user)
           ->orderBy("month", "ASC")
           ->addOrderBy("day", "ASC")
           ->groupBy("year")
           ->addGroupBy("month")
           ->addGroupBy("day");

        return $qb->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();
    }
}