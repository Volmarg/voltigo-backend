<?php


namespace App\Controller\Storage;


use App\Entity\Storage\FrontendErrorStorage;
use App\Repository\Storage\FrontendErrorStorageRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Handles the frontend error storage logic
 *
 * Class FrontendDataStorageController
 * @package App\Controller\Storage
 */
class FrontendErrorStorageController extends AbstractController
{
    /**
     * @var FrontendErrorStorageRepository $frontendErrorStorageRepository
     */
    private FrontendErrorStorageRepository $frontendErrorStorageRepository;

    public function __construct(FrontendErrorStorageRepository $frontendErrorStorageRepository)
    {
        $this->frontendErrorStorageRepository = $frontendErrorStorageRepository;
    }

    /**
     * Will save the frontend error storage entry (create or update the existing one)
     * @param FrontendErrorStorage $frontendErrorStorage
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FrontendErrorStorage $frontendErrorStorage): void
    {
        $this->frontendErrorStorageRepository->save($frontendErrorStorage);
    }
}