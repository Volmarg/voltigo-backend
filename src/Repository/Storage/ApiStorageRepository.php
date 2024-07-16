<?php

namespace App\Repository\Storage;

use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\ApiStorage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApiStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiStorage[]    findAll()
 * @method ApiStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiStorageRepository extends AbstractStorageRepository
{
    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    public function __construct(ManagerRegistry $registry, ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
        parent::__construct($registry, ApiStorage::class);
    }

}
