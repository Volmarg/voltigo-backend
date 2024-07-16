<?php

namespace App\Repository\Security;

use App\Entity\Security\User;
use App\Listener\Bundles\LexitJwtAuthentication\JwtCreatedListener;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Will return user by given email (if is not soft deleted)
     *
     * @param string $email
     * @param bool   $nonDeletedOnly
     *
     * @return User|null
     */
    public function getOneByEmail(string $email, bool $nonDeletedOnly = false): ?User
    {
        $criteria = [
            User::FIELD_EMAIL => $email,
        ];

        if ($nonDeletedOnly) {
            $criteria[User::FIELD_DELETED] = 0;
        }

        $user = $this->findOneBy($criteria);

        return $user;
    }

    /**
     * Will return user by given id (if is not soft deleted)
     *
     * @param string $id
     * @return User|null
     */
    public function getOneById(string $id): ?User
    {
        $user = $this->findOneBy([
            User::FIELD_ID      => $id,
            User::FIELD_DELETED => 0,
        ]);

        return $user;
    }

    /**
     * Will either create new record in db or update existing one
     *
     * @param UserInterface $user
     */
    public function save(UserInterface $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Will return all users (excludes: soft deleted)
     *
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->findBy([
            User::FIELD_DELETED => 0,
        ]);
    }

    /**
     * Remove entities older than `x` hours
     *
     * @param int $hoursCount
     *
     * @return int
     * @throws Exception
     */
    public function removeOlderThanHours(int $hoursCount): int
    {
        $beforeDate = (new \DateTime())->modify("-{$hoursCount} HOURS")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("u")
                     ->from(User::class, "u")
                     ->where(
                         $queryBuilder->expr()->andX(
                             "u.modified < :beforeDate",
                             "u.deleted = 1"
                         )
                     )
                     ->setParameter("beforeDate", $beforeDate);

        /**
         * Must be made this way as the query builder based deletion is not triggering cascade removal
         * @var User[] $entities
         */
        $entities     = $queryBuilder->getQuery()->execute();
        $removalCount = count($entities);
        foreach ($entities as $entity) {

            // transaction is needed due to removal of all the user related entities
            $this->_em->beginTransaction();
            try {
                // some entities need to be removed manually first (like self-referencing relations)
                $allRemovedAmqpEntries = [];
                foreach ($entity->getAmqpStorageEntries() as $amqpStorageEntry) {
                    $allRemovedAmqpEntries = [
                        $amqpStorageEntry,
                        ...$allRemovedAmqpEntries,
                        ...$amqpStorageEntry->getAllNestedRelatedStorageEntries(),
                    ];
                }

                array_filter($allRemovedAmqpEntries);
                foreach ($allRemovedAmqpEntries as $removedAmqpEntry) {
                    $this->_em->remove($removedAmqpEntry);
                    $this->_em->flush();
                }

                $this->_em->remove($entity);
                $this->_em->flush();
                $this->_em->commit();
            } catch (Exception $e) {
                $this->_em->rollback();
                throw $e;
            }
        }

        return $removalCount;
    }

    /**
     * Websocket causes that entity fetched in its lifecycle is never refreshed. And that's normal for it.
     * See articles:
     * - {@link https://stackoverflow.com/a/51582007/9668115}
     * - {@link https://stackoverflow.com/a/51582007}
     *
     * This solves the problem.
     *
     * > IMPORTANT < all relations must be re-freshed separately.
     *
     * Calling refresh just on the {@see User} will update the {@see User} state,
     * the objects related to it will remain as before
     *
     * Atm. refreshing only the data that is needed for the jwt token in: {@see JwtCreatedListener}.
     */
    public function refreshUserWithRelations(User $user): void
    {
        $this->_em->refresh($user);
        $this->_em->refresh($user->getAddress());
        foreach ($user->getPointHistory() as $history) {
            $this->_em->refresh($history);
        }

        foreach ($user->getUploadedFiles() as $uploadedFile) {
            $this->_em->refresh($uploadedFile);
        }

        foreach ($user->getOrders() as $order) {
            $this->_em->refresh($order);
        }
    }
}
