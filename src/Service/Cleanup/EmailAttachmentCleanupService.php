<?php

namespace App\Service\Cleanup;

use App\Entity\Email\EmailAttachment;
use App\Repository\Email\EmailAttachmentRepository;
use App\Service\Logger\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Handles cleaning the attachments of the {@see EmailAttachment}
 */
class EmailAttachmentCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly EmailAttachmentRepository $emailAttachmentRepository,
        private readonly int                       $attachmentMaxLifetime,
        private readonly LoggerService             $logger,
        private readonly EntityManagerInterface    $entityManager
    ){}

    /**
     * {@inheritDoc}
     */
    public function cleanUp(): int
    {
        $countOfRemoved = 0;
        $entities = $this->emailAttachmentRepository->getEntitiesForCleanUp($this->attachmentMaxLifetime);
        foreach ($entities as $entity) {
            if (!$entity->isRemoveFile()) {
                continue;
            }

            $fullPath      = $entity->getPath() . $entity->getFileName();
            $isFileRemoved = unlink($entity->getPath());
            if (!$isFileRemoved) {
                $this->logger->critical("Could not remove the file under path: " . $entity->$fullPath);
                continue;
            }

            $countedFilesInFolder = glob($entity->getPath());
            if (!empty($countedFilesInFolder)) {
                continue;
            }

            $isFolderRemoved = unlink($entity->getPath());
            if (!$isFolderRemoved) {
                $this->logger->critical("Could not remove the folder under path: " . $entity->getPath());
            }

            try {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->logger->critical("Could not remove the email attachment entity of id under path: " . $entity->getId());
                $this->logger->logException($e);
                continue;
            }

            $countOfRemoved++;
        }

        return $countOfRemoved;
    }
}
