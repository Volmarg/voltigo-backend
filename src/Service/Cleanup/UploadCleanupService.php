<?php

namespace App\Service\Cleanup;

use App\Entity\File\UploadedFile;
use App\Entity\Security\User;
use App\Enum\File\UploadedFileSourceEnum;
use App\Repository\File\UploadedFileRepository;
use App\Repository\Security\UserRepository;
use App\Service\Directory\DirectoryService;
use App\Service\File\UploadedFile\UploadedFileService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles cleaning the {@see UploadedFile}
 */
class UploadCleanupService implements CleanupServiceInterface
{
    private string $uploadFolderPath;
    private int $uploadedFileCleanupAfterHours;
    private int $uploadedFileCleanupWhenUserDeletedAfterHours;

    public function __construct(
        ParameterBagInterface                   $parameterBag,
        KernelInterface                         $kernel,
        private readonly UploadedFileRepository $uploadedFileRepository,
        private readonly UserRepository         $userRepository,
        private readonly LoggerInterface        $logger,
        private readonly UploadedFileService    $uploadedFileService,
        private readonly EntityManagerInterface $entityManager
    ){
        $this->uploadFolderPath                             = $kernel->getPublicDirectoryPath() . $parameterBag->get('upload.linked.dir.in.public');
        $this->uploadedFileCleanupAfterHours                = $parameterBag->get('uploaded_file.cleanup_after_hours');
        $this->uploadedFileCleanupWhenUserDeletedAfterHours = $parameterBag->get('uploaded_file.cleanup_when_user_deleted_after_hours');
    }

    /**
     * Why using finder for this? Because even if it should NOT happen there is very low chance that there can be some files
     * which are not registered in database.
     *
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        $finder = new Finder();
        $finder->depth("0");
        $userDirectories = array_map(
            fn(SplFileInfo $fileInfo) => $fileInfo->getPathname(),
            iterator_to_array($finder->directories()->in($this->uploadFolderPath))
        );

        $cleanupCount = 0;
        foreach ($userDirectories as $userUploadDirectory){
            $userId = (int)basename($userUploadDirectory);
            $finder = new Finder();
            $finder->depth("0");

            $uploadSourceDirectories = array_map(
                fn(SplFileInfo $fileInfo) => $fileInfo->getPathname(),
                iterator_to_array($finder->directories()->in($userUploadDirectory))
            );

            $cleanupCount += $this->handleAllDataClear($userUploadDirectory, $userId);
            foreach ($uploadSourceDirectories as $uploadSourceDirectory) {
                $cleanupCount += $this->removeUnregisteredFiles($uploadSourceDirectory, $userId);

                $uploadSource = basename($uploadSourceDirectory);
                switch ($uploadSource) {
                    case UploadedFileSourceEnum::EASY_EMAIL->value:
                        $cleanupCount += $this->handleEasyEmailCleanup($userId);
                    break;
                }
            }
        }

        return $cleanupCount;
    }

    /**
     * Will remove files which are present in the upload folder structure but are not present in DB as {@see UploadedFile}
     * This should generally not happen but if something would go wrong in removal process then it's theoretically
     * possible
     *
     * @return int - count of removed files
     */
    private function removeUnregisteredFiles(string $uploadSourceDirectory, int $userId): int
    {
        $user = $this->userRepository->getOneById($userId);
        /** {@see UploadCleanupService::handleAllDataClear()} */
        if (empty($user)) {
            return 0;
        }

        $finder = new Finder();

        $foundFileNamesWithPaths = [];
        foreach ($finder->files()->in($uploadSourceDirectory) as $fileInfo) {
            $foundFileNamesWithPaths[$fileInfo->getFilename()] = $fileInfo->getPathname();
        }

        $entities = $this->uploadedFileService->findAllForUserByDirScan($user);
        foreach ($entities as $entity) {
            if (array_key_exists($entity->getLocalFileName(), $foundFileNamesWithPaths)) {
                unset($foundFileNamesWithPaths[$entity->getLocalFileName()]);
            }
        }

        $removedFilesCount = 0;
        foreach ($foundFileNamesWithPaths as $unregisteredFile) {
            if (!unlink($unregisteredFile)) {
                $this->logger->warning("Could nor remove unregistered upload file: {$unregisteredFile}. Maybe entity got removed but file didnt in earlier run?");
                continue;
            }

            $removedFilesCount++;
        }

        return $removedFilesCount;
    }

    /**
     * If user is deleted {@see User::isDeleted()} then will delete all his data,
     * If there was some data found for user which is not present in database then will wipe all that data.
     *
     * @param string $userUploadDirectory
     * @param int    $userId
     *
     * @return int
     */
    private function handleAllDataClear(string $userUploadDirectory, int $userId): int
    {
        // case 1 - user does not exist in db anymore (should not happen) >> wipe the directory
        $user = $this->userRepository->getOneById($userId);
        if (empty($user)) {
            $this->logger->info("User with id'{$userId}' does not exist, will be cleaning his data.");
            return $this->removeAllFilesInUserDirectory($userUploadDirectory);
        }

        $deletedUserDataMaxLifetime = (clone $user->getModified())->modify("+{$this->uploadedFileCleanupWhenUserDeletedAfterHours} HOUR")->getTimestamp();
        $isUserDataDeletionDueTime  = $deletedUserDataMaxLifetime <= (new DateTime())->getTimestamp();

        // case 2 - user is deleted (the modified date is updated in such case), wipe all his data after given amount of time
        if (
                $user->isDeleted()
            &&  $isUserDataDeletionDueTime
        ){
            $this->logger->info("
                User with id: '{$userId}' is deleted, 
                and the max lifetime period of his data has been reached, will be cleaning his data
            ");

            $this->removeAllEntitiesForUser($user);
            return $this->removeAllFilesInUserDirectory($userUploadDirectory);
        }

        return 0;
    }

    /**
     * Remove all {@see UploadedFile} entities for user
     *
     * @param User $user
     */
    private function removeAllEntitiesForUser(User $user): void
    {
        $entities = $this->uploadedFileService->findAllForUserByDirScan($user);
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();
    }

    /**
     * Will remove all FILES ON SERVER in the user upload folder
     *
     * @param string $userUploadDirectory
     *
     * @return int - count of removed entries
     */
    private function removeAllFilesInUserDirectory(string $userUploadDirectory): int
    {
        $dataCountBeforeRemoval = (new Finder())->in($userUploadDirectory)->count();

        $this->logger->info("Removing all the files from folder: {$userUploadDirectory}");
        if (!DirectoryService::rmRf($userUploadDirectory)) {
            $this->logger->critical("Could not remove all the files from folder: {$userUploadDirectory}");
        }

        if (!file_exists($userUploadDirectory)) {
            return $dataCountBeforeRemoval;
        }

        $dataCountAfterRemoval = (new Finder())->in($userUploadDirectory)->count();

        return ($dataCountBeforeRemoval - $dataCountAfterRemoval);
    }

    /**
     * Handles removing the images uploaded for EasyEmail (template builder used on front)
     *
     * @param int    $userId
     *
     * @return int
     */
    private function handleEasyEmailCleanup(int $userId): int
    {
        $user = $this->userRepository->getOneById($userId);
        /** {@see UploadCleanupService::handleAllDataClear()} */
        if (empty($user)) {
            return 0;
        }

        $removedEntriesCount  = 0;
        $allDeletableEntities = $this->uploadedFileRepository->findDeletableForUser($user, UploadedFileSourceEnum::EASY_EMAIL);
        foreach ($allDeletableEntities as $deletableEntity) {
            $currentStamp = (new \DateTime())->getTimestamp() ;
            $maxLifetime  = (clone $deletableEntity->getCreated())->modify("+{$this->uploadedFileCleanupAfterHours} HOUR")->getTimestamp();

            if (
                    $maxLifetime > $currentStamp
                ||  $this->uploadedFileService->isReferencedAnywhere($deletableEntity)
            ) {
                continue;
            }

            if ($deletableEntity->isOnServer()){
                if (!unlink($deletableEntity->getPathWithFileName())) {
                    $this->logger->warning("Could not remove the easy email upload file: {$deletableEntity->getPathWithFileName()}. Skipping entity removal.");
                    continue;
                }
            }

            $this->entityManager->remove($deletableEntity);
            $removedEntriesCount++;
        }
        $this->entityManager->flush();

        return $removedEntriesCount;
    }
}