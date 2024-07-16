<?php

namespace App\Repository\Job;

use App\Entity\Email\Email;
use App\Entity\Job\JobApplication;
use App\Entity\Security\User;
use App\Service\Serialization\ObjectSerializerService;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method JobApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobApplication[]    findAll()
 * @method JobApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<JobApplication>
 */
class JobApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    /**
     * Will either create new entity or update existing one
     *
     * @param JobApplication $jobApplication
     *
     */
    public function save(JobApplication $jobApplication): void
    {
        $this->_em->persist($jobApplication);
        $this->_em->flush();
    }

    /**
     * Will return all job applications for user
     *
     * @param User     $user
     * @param int|null $daysInterval
     *
     * @return JobApplication[]
     */
    public function findAllForUser(User $user, ?int $daysInterval = null): array {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("ja")
            ->from(JobApplication::class, "ja")
            ->join(Email::class, "e", Join::WITH, "e.id = ja.email")
            ->where("ja.user = :user")
            ->setParameter("user", $user)
            ->orderBy("ja.created", "DESC");

        if (!empty($daysInterval)) {
            $intervalDate = (new DateTime())->modify("-{$daysInterval} DAYS");
            $queryBuilder->andWhere("ja.created > :intervalDate")
                ->setParameter("intervalDate", $intervalDate);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return all job applications for user.
     *
     * Let's face it - this is not pretty, yet there are strong reasons for this.
     * Using the {@see AbstractQuery::HYDRATE_ARRAY} because it speeds up fetching data
     * from DB, where earlier it was ~2s for ~200 entries, and now it's ~100ms.
     *
     * The normalisation part... well, is not pretty as well but it represents the structure that
     * would've been sent to front if the {@see ObjectSerializerService} would be used. In fact,
     * it was used earlier, but it all was just too slow.
     *
     * Front is already expecting certain data structure to be delivered, and this normalisation array
     * is what keeps the structure in sync (working).
     *
     * If this ever is going to be extended then it must be cleaned up / be made more properly,
     * it's not super bad, but just saying it's not perfect.
     *
     * @param User     $user
     * @param int|null $daysInterval
     *
     * @return string[][]
     */
    public function findAllMinimumForUser(User $user, ?int $daysInterval = null): array {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("
            ja.status       AS status,
            ja.created      AS created,
            e.id            AS emailId,
            joi.id          AS id,
            joi.title       AS title,
            joi.companyName AS companyName,
            joi.originalUrl AS originalUrl,
            joi.externalId  AS externalId
        ")
            ->from(JobApplication::class, "ja")
            ->join("ja.jobOffer", "joi")
            ->join(Email::class, "e", Join::WITH, "e.id = ja.email")
            ->where("ja.user = :user")
            ->setParameter("user", $user)
            ->orderBy("ja.created", "DESC");

        if (!empty($daysInterval)) {
            $intervalDate = (new DateTime())->modify("-{$daysInterval} DAYS");
            $queryBuilder->andWhere("ja.created > :intervalDate")
                ->setParameter("intervalDate", $intervalDate);
        }

        $rows = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();

        $normalisedData = [];
        foreach ($rows as $row) {
            $id      = $row['id'];
            $emailId = $row['emailId'];
            $status  = $row['status'];

            /** @var DateTime $created */
            $created       = $row['created'];
            $createdString = $created->format("Y-m-d H:i:s");

            // unsetting to avoid confusion when using this data structure (on front)
            unset($row['id']);
            unset($row['emailId']);
            unset($row['created']);
            unset($row['status']);

            $normalisedData[] = [
                'id'       => $id,
                'emailId'  => $emailId,
                'status'   => $status,
                'created'  => $createdString,
                'jobOffer' => $row,
            ];
        }

        return $normalisedData;
    }

    /**
     * @param array $data - where key is the company name and value is the job title
     * @return JobApplication[]
     */
    public function findByCompanyNamesAndTitles(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select("
            MAX(ja.id) AS id,
            MD5(CONCAT(jo.title,jo.companyName)) AS hash -- needed for grouping, this wont work directly in it
        ")
            ->from(JobApplication::class, "ja")
            ->join("ja.jobOffer", "jo")
            ->where("1 = 1")
            ->groupBy('hash');

        $andExpr = [];
        $params  = [];
        foreach ($data as $companyName => $jobTitle) {
            $hashParamNameTitle   = "title_" . md5($companyName . $jobTitle);
            $hashParamNameCompany = "company_" . md5($companyName . $jobTitle);

            $andExpr[] = $qb->expr()->andX(
                "jo.title = :{$hashParamNameTitle}",
                "jo.companyName = :{$hashParamNameCompany}"
            );

            $params[$hashParamNameTitle]   = $jobTitle;
            $params[$hashParamNameCompany] = $companyName;
        }

        $qb->andWhere($qb->expr()->orX(...$andExpr));

        /**
         * It's possible that user applies few times to same offer over-time,
         * It's impossible to fetch the highest ids with doctrine via one query
         * So first the highest ids are being fetched and then the applications
         */
        $resultData = $qb->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute($params);
        $highestIds = array_column($resultData, "id");
        $applications = $this->findBy([
            "id" => $highestIds,
        ]);

        return $applications;
    }

    /**
     * @param int      $offerId
     * @param User     $user
     * @param int|null $maxAppliedDaysOffset
     *
     * @return JobApplication|null
     * @throws NonUniqueResultException
     */
    public function findById(int $offerId, User $user, int $maxAppliedDaysOffset = null): ?JobApplication
    {
        $maxDateOffset = (new DateTime())->modify("-{$maxAppliedDaysOffset} DAYS");

        $qb = $this->_em->createQueryBuilder();
        $qb->select("ja")
            ->from(JobApplication::class, "ja")
            ->join("ja.jobOffer", "jis")
            ->where("ja.user = :userId")
            ->andWhere("jis.externalId = :offerId")
            ->setParameter("offerId", $offerId)
            ->setParameter("userId", $user->getId())
            ->orderBy("ja.id", 'DESC')
            ->setMaxResults(1);

        if (!is_null($maxAppliedDaysOffset)) {
            $qb->andWhere("ja.created >= :maxDateOffset")
                ->setParameter("maxDateOffset", $maxDateOffset->format("Y-m-d H:i:s"));
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

}
