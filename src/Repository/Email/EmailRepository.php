<?php

namespace App\Repository\Email;

use App\Entity\Email\Email;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Email|null find($id, $lockMode = null, $lockVersion = null)
 * @method Email|null findOneBy(array $criteria, array $orderBy = null)
 * @method Email[]    findAll()
 * @method Email[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Email>
 */
class EmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Email::class);
    }

    /**
     * Will save the entity (create or update the existing one)
     *
     * @param Email $email
     */
    public function save(Email $email): void
    {
        $this->_em->persist($email);
        $this->_em->flush();
    }

    /**
     * Will return all E-mails that has been sent
     *
     * @return Email[]
     */
    public function getAllSentEmails(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("em")
            ->from(Email::class, "em")
            ->where("em.externalId IS NOT NULL");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return all emails that should be sent to external tool
     * @return Email[]
     */
    public function getAllEmailsForSending(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("em")
            ->from(Email::class, "em")
            ->where("em.externalId IS NULL")
            ->andWhere("em.anonymized != 1")
            ->andWhere("em.error IS NULL")
            ->andWhere("em.status IN (:statuses)")
            ->setParameter('statuses', [
                Email::KEY_STATUS_PENDING,
            ]);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $identifier
     * @param string $email
     * @param int    $minutesOffset
     * @param bool   $senderBased
     *
     * @return Email[]
     */
    public function getLastEmailsByIdentifier(string $identifier, string $email, int $minutesOffset, bool $senderBased = false): array
    {
        $afterDate    = (new DateTime())->modify("-{$minutesOffset} MINUTES");
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("em")
            ->from(Email::class, "em")
            ->where("em.identifier = :identifier")
            ->andWhere("em.created BETWEEN :afterDate AND NOW()")
            ->setParameter("identifier", $identifier)
            ->setParameter("afterDate", $afterDate);

        if ($senderBased) {
            $usedEmail = $email;
            $queryBuilder->join("em.sender", "u")
                         ->andWhere("u.email = :email");
        } else {
            $usedEmail = "%{$email}%";
            $queryBuilder->andWhere("em.recipients LIKE :email"); // must be like because emails are serialized
        }

        $queryBuilder->setParameter("email", $usedEmail);

        $results = $queryBuilder->getQuery()->execute();

        return $results;
    }

    /**
     * Find all the E-Mails by ids
     *
     * @param array $ids
     * @param bool  $includeAnonymized
     * @param bool  $includeWithErrors
     *
     * @return array
     */
    public function findByIds(array $ids, bool $includeAnonymized = false, bool $includeWithErrors = false): array
    {
        $criteria = [
            'id' => $ids,
        ];

        if (!$includeAnonymized) {
            $criteria['anonymized'] = 0;
        }

        if (!$includeWithErrors) {
            $criteria['error'] = NULL;
        }

        return $this->findBy($criteria);
    }

    /**
     * Faster alternative to the {@see Email::getFirstRecipient()}, it's not known why the mentioned
     * method is so slow, just guessing that it may be related to how doctrine load entity and fetches
     * the large body content?
     *
     * @param int $id
     *
     * @return string
     */
    public function findFirstRecipient(int $id): string
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('e.recipients AS recipients')
            ->from(Email::class, 'e')
            ->where('e.id = :id')
            ->setParameter('id', $id);

        $serializedData = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SINGLE_SCALAR)->execute();
        $emails         = unserialize($serializedData);

        return $emails[array_key_first($emails)];
    }
}
