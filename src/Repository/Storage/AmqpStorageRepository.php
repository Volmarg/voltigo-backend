<?php

namespace App\Repository\Storage;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\AmqpStorage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AmqpStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method AmqpStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method AmqpStorage[]    findAll()
 * @method AmqpStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmqpStorageRepository extends AbstractStorageRepository
{
    public function __construct(
        ManagerRegistry $registry,
    )
    {
        parent::__construct($registry, AmqpStorage::class);
    }

    /**
     * Will return {@see AmqpStorage} entry for given uniqueId, or null if none is found
     *
     * @param string $uniqueId
     *
     * @return AmqpStorage|null
     */
    public function findByUniqueId(string $uniqueId): ?AmqpStorage
    {
        return $this->findOneBy([
            "uniqueId" => $uniqueId
        ]);
    }

}
