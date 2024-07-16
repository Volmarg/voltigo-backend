<?php

namespace App\Service\File\UploadedFile;

use App\Entity\File\UploadedFile;
use App\Entity\Security\User;
use App\Repository\File\UploadedFileRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class UploadedFileService
{
    private string $uploadFolderPath;

    public function __construct(
        KernelInterface                         $kernel,
        ParameterBagInterface                   $parameterBag,
        private readonly UploadedFileRepository $uploadedFileRepository
    ){
        $this->uploadFolderPath = $kernel->getPublicDirectoryPath() . $parameterBag->get('upload.linked.dir.in.public');
    }

    /**
     * Check if the file is referenced anywhere like for example in queued E-Mails etc.
     *
     * @param UploadedFile $uploadedFile
     *
     * @return bool
     */
    public function isReferencedAnywhere(UploadedFile $uploadedFile): bool
    {
        return (
                $this->uploadedFileRepository->isReferencedInAnyEmail($uploadedFile)
            ||  $this->uploadedFileRepository->isReferencedInAnyEmailTemplate($uploadedFile)
        );
    }

    /**
     * @param User $user
     *
     * @return UploadedFile[]
     */
    public function findAllForUserByDirScan(User $user): array
    {
        $userUploadDir = $this->uploadFolderPath . $user->getId();
        $finder        = new Finder();
        $filesNames    = array_map(
            fn(SplFileInfo $fileInfo) => basename($fileInfo->getPathname()),
            iterator_to_array($finder->files()->in($userUploadDir))
        );

        $uploadedFiles = $this->uploadedFileRepository->findByLocalFileNames($filesNames);

        return $uploadedFiles;
    }

}