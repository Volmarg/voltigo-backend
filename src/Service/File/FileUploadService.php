<?php

namespace App\Service\File;

use App\DTO\Internal\Upload\UploadConfigurationDTO;
use App\Enum\File\UploadedFileSourceEnum;
use App\Exception\File\UploadRestrictionException;
use App\Exception\File\UploadValidationException;
use App\Repository\File\UploadedFileRepository;
use App\Service\File\Path\PathService;
use App\Service\Logger\LoggerService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\Scanner\FileScannerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\File\UploadedFile as UploadedFileEntity;
use TypeError;

/**
 * Handles uploading files,
 * Based on the:
 * -{@link https://symfony.com/doc/current/controller/upload_file.html}
 */
class FileUploadService
{
    private string $baseUploadDirectoryPath;
    private string $accessLinkDirectoryInPublic;
    private string $temporaryUploadDirectoryPath;

    /**
     * @var Array<UploadedFileSourceEnum>
     */
    private const PUBLICLY_LINKED_UPLOAD_SOURCE_ENUMS = [
        UploadedFileSourceEnum::PROFILE_IMAGE,
        UploadedFileSourceEnum::EASY_EMAIL,
        UploadedFileSourceEnum::CV,
    ];

    /**
     * @param SluggerInterface             $slugger
     * @param ParameterBagInterface        $parameterBag
     * @param JwtAuthenticationService     $jwtAuthenticationService
     * @param LoggerService                $logger
     * @param EntityManagerInterface       $entityManager
     * @param FileScannerService           $fileScannerService
     * @param UploadedFileRepository       $uploadedFileRepository
     * @param FileUploadValidator          $fileUploadValidator
     * @param FileUploadConfigurator       $fileUploadConfigurator
     * @param FileUploadRestrictionService $fileUploadRestrictionService
     */
    public function __construct(
        private readonly SluggerInterface             $slugger,
        ParameterBagInterface                         $parameterBag,
        private readonly JwtAuthenticationService     $jwtAuthenticationService,
        private readonly LoggerService                $logger,
        private readonly EntityManagerInterface       $entityManager,
        private readonly FileScannerService           $fileScannerService,
        private readonly UploadedFileRepository       $uploadedFileRepository,
        private readonly FileUploadValidator          $fileUploadValidator,
        private readonly FileUploadConfigurator       $fileUploadConfigurator,
        private readonly FileUploadRestrictionService $fileUploadRestrictionService
    )
    {
        $this->baseUploadDirectoryPath      = $parameterBag->get("upload.directory");
        $this->accessLinkDirectoryInPublic  = $parameterBag->get("upload.linked.dir.in.public");
        $this->temporaryUploadDirectoryPath = $parameterBag->get("upload.tmp.dir");
    }

    /**
     * Will validate the uploaded file and save it in proper target directory
     *
     * @param UploadedFile $file
     * @param string       $uploadConfigId
     * @param string|null  $userDefinedName
     * @param float        $frontendFileSizeBytes
     *
     * @return UploadedFileEntity
     * @throws UploadRestrictionException
     * @throws UploadValidationException
     * @throws Exception
     */
    public function handleUpload(UploadedFile $file, string $uploadConfigId, ?string $userDefinedName, float $frontendFileSizeBytes): UploadedFileEntity
    {
        $this->fileScannerService->scan($file->getPathname());

        try {
            $uploadConfiguration = $this->fileUploadConfigurator->getConfiguration($uploadConfigId);
            $fileSourceEnum      = UploadedFileSourceEnum::tryFrom($uploadConfiguration->getSource());

            $restrictionValidationResult = $this->fileUploadRestrictionService->validate($fileSourceEnum);
            if (!$restrictionValidationResult->isSuccess()) {
                $ex = new UploadRestrictionException();
                $ex->setValidationResult($restrictionValidationResult);

                throw $ex;
            }

            $this->fileUploadValidator->init($file, $frontendFileSizeBytes);
            $this->fileUploadValidator->preUploadValidation($file, $uploadConfiguration);

            $this->entityManager->beginTransaction();
            $this->ensureBaseUploadDirectoryExist();

            $uploadDirPath  = $this->buildUploadDirectoryPath($fileSourceEnum);

            $this->createUploadDirectory($uploadDirPath);

            $savedFileName = $this->buildSavedFileName($file);
            $uploadEntity  = $this->saveUploadEntity($file, $savedFileName, $uploadDirPath, $uploadConfiguration, $userDefinedName);

            $targetPath = $uploadDirPath . $savedFileName;
            $this->moveFromTemp($file->getPathname(), $targetPath);
            if (!$uploadEntity) {
                $this->handleUnsavedEntity($targetPath);
            }

            $this->fileUploadValidator->preSaveValidation($targetPath);
            $this->entityManager->commit();
        } catch (FileException | UploadValidationException $e) {
            if (file_exists($file->getPathname())) {
                unlink($file->getPathname());
            }

            $this->logger->logException($e);
            $this->entityManager->rollback();

            throw $e;
        }

        return $uploadEntity;
    }

    /**
     * @param int $id
     * @return string
     */
    public function getPathForEntityId(int $id)
    {
        $uploadedFileEntity = $this->uploadedFileRepository->find($id);

        return $uploadedFileEntity->getPathWithFileName();
    }

    /**
     * Will create file in temporary folder and return the {@see UploadedFile}
     *
     * @param string $fileContent
     * @param string $originalFileName
     *
     * @return UploadedFile
     */
    public function saveInTemporaryDir(string $fileContent, string $originalFileName): UploadedFile
    {
        $fileName     = uniqid("temporary_upload");
        $temporaryDir = PathService::setTrailingSlash($this->temporaryUploadDirectoryPath);

        $extension         = preg_replace("#(.*)\.(.*)#", ".$2", $originalFileName) ?? "";
        $temporaryFilePath = $temporaryDir . $fileName . $extension;
        file_put_contents($temporaryFilePath, $fileContent);

        $uploadedFile = new UploadedFile($temporaryFilePath, $originalFileName);

        return $uploadedFile;
    }

    /**
     * Handles file removal
     *
     * @param UploadedFileEntity $uploadedFile
     *
     * @return bool
     */
    public function deleteFile(UploadedFileEntity $uploadedFile): bool
    {
        $fileExists = file_exists($uploadedFile->getPathWithFileName());
        if ($fileExists) {
            $fileContent = file_get_contents($uploadedFile->getPathWithFileName());
        }

        try {
            $this->entityManager->beginTransaction();
            if (!$fileExists) {
                $this->entityManager->remove($uploadedFile);
                $this->entityManager->flush();
                $this->entityManager->commit();
                return true;
            }

            $isRemoved = @unlink($uploadedFile->getPathWithFileName());
            if (!$isRemoved) {
                $this->logger->critical("Could not remove uploaded file with id: {$uploadedFile->getId()}, reason: " . error_get_last());
                return false;
            }

            $this->entityManager->remove($uploadedFile);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();

            $info = [
                "info" => "Could not remove file with id: {$uploadedFile->getId()}",
            ];

            if (isset($isRemoved)) {
                $isReverted = @file_put_contents($uploadedFile->getPathWithFileName(), $fileContent);
                if (!$isReverted) {
                    $this->logger->critical("Tried to revert the removed file, but could not do that, something went wrong with reversing", [
                        "possibleIssue" => error_get_last(),
                        "info"          => "Keep in mind that this error might be totally unrelated, unknown if file put content errors are caught by it"
                    ]);
                }
            }

            $this->logger->logException($e, $info);

            return false;
        }

        return true;
    }

    /**
     * Builds the path to the folder in which file will be saved to
     *
     * @param UploadedFileSourceEnum $fileSourceEnum
     *
     * @return string
     */
    private function buildUploadDirectoryPath(UploadedFileSourceEnum $fileSourceEnum): string
    {
        $datePart = (new \DateTime())->format("Y")
            . DIRECTORY_SEPARATOR . (new \DateTime())->format("m")
            . DIRECTORY_SEPARATOR . (new \DateTime())->format("d");

        $fullPath = $this->buildBaseUploadPath()
            . $fileSourceEnum->name
            . DIRECTORY_SEPARATOR
            . $datePart
            . DIRECTORY_SEPARATOR;

        return $fullPath;
    }

    /**
     * Returns the linked directory path that can be used for public access,
     * Keep in mind that not all files will have public access to prevent data breach etc.
     *
     * Also keep in mind that the path returned from here MUST always be relative toward `public` folder of backend
     *
     * @param string $uploadedFilePath
     *
     * @return string
     */
    private function buildLinkedDirectoryPathInPublic(string $uploadedFilePath): string
    {
        $baseUploadPath         = $this->buildBaseUploadPath();
        $replacedLink           = str_replace($baseUploadPath, "", $uploadedFilePath);
        $linkedFilePathInPublic = $this->accessLinkDirectoryInPublic
                                . $this->jwtAuthenticationService->getUserFromRequest()->getId()
                                . DIRECTORY_SEPARATOR
                                . $replacedLink;

        return $linkedFilePathInPublic;
    }

    /**
     * Creates the base path for uploaded files
     *
     * @return string
     */
    private function buildBaseUploadPath(): string
    {
        $baseUploadPath = PathService::setTrailingSlash($this->baseUploadDirectoryPath);
        $baseUploadPath .= $this->jwtAuthenticationService->getUserFromRequest()->getId() . DIRECTORY_SEPARATOR;
        return $baseUploadPath;
    }

    /**
     * Will attempt to create the folder path used for current upload
     *
     * @param string $path
     */
    private function createUploadDirectory(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        $isSuccess = @mkdir($path, 0755, true);
        if (!$isSuccess) {
            throw new FileException("Could not create upload folder: {$path}. Error: " . json_encode(error_get_last()));
        }
    }

    /**
     * Makes sure that the base upload directory exists, if not then will create that folder
     */
    private function ensureBaseUploadDirectoryExist(): void
    {
        if (!file_exists($this->baseUploadDirectoryPath)) {
            $isCreated = @mkdir($this->baseUploadDirectoryPath, 0755, true);
            if (!$isCreated) {
                throw new FileException("Could not create base upload folder: {$this->baseUploadDirectoryPath}. Error: " . json_encode(error_get_last()));
            }
        }
    }

    /**
     * Will build name of the saved file,
     * File will be saved with this name on server
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    private function buildSavedFileName(UploadedFile $file): string
    {
        $safeFilename = $this->slugger->slug($file->getClientOriginalName());
        $extension    = $file->guessExtension() ?? $file->getExtension();
        $fileName     = $safeFilename . '-' . uniqid() . '.' . $extension;

        return $fileName;
    }

    /**
     * Saving the uploaded file entity in db
     *
     * @param UploadedFile           $file
     * @param string                 $savedFileName
     * @param string                 $uploadDirPath
     * @param UploadConfigurationDTO $uploadConfiguration
     * @param string|null            $userDefinedName
     *
     * @return UploadedFileEntity|null
     */
    private function saveUploadEntity(
        UploadedFile $file,
        string $savedFileName,
        string $uploadDirPath,
        UploadConfigurationDTO $uploadConfiguration,
        ?string $userDefinedName,
    ): ? UploadedFileEntity
    {
        try {
            $fileSourceEnum  = UploadedFileSourceEnum::tryFrom($uploadConfiguration->getSource());
            $user            = $this->jwtAuthenticationService->getUserFromRequest();
            $fileSizeMb      = ($file->getSize() / 1024 / 1024);
            $cleanedUserName = $this->cleanUserDefinedName($userDefinedName);

            $uploadEntity = new UploadedFileEntity();
            $uploadEntity->setOriginalName($file->getClientOriginalName());
            $uploadEntity->setLocalFileName($savedFileName);
            $uploadEntity->setUserBasedName($cleanedUserName);
            $uploadEntity->setPath($uploadDirPath);
            $uploadEntity->setUser($user);
            $uploadEntity->setSizeMb($fileSizeMb);
            $uploadEntity->setMimeType($file->getMimeType() ?? "unknown");
            $uploadEntity->setSource($fileSourceEnum);

            foreach (self::PUBLICLY_LINKED_UPLOAD_SOURCE_ENUMS as $linkedUploadEnum) {
                if ($linkedUploadEnum->value === $fileSourceEnum->value) {
                    $linkedFilePath = $this->buildLinkedDirectoryPathInPublic($uploadDirPath);
                    $uploadEntity->setPublicPath($linkedFilePath);
                    break;
                }
            }

            $this->entityManager->persist($uploadEntity);
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Failed saving uploaded file: {$file->getPath()}", [
                "exception" => [
                    "class"   => $e::class,
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            return null;
        }

        return $uploadEntity;
    }

    /**
     * Keep in mind that front has information which characters are allowed for name, so if this rule changes
     * then front displayed text must also be adjusted
     *
     * @param string|null $userDefinedName
     *
     * @return string|null
     */
    private function cleanUserDefinedName(?string $userDefinedName): ?string
    {
        if (is_null($userDefinedName)) {
            return null;
        }

        $modifiedName = preg_replace("#[^\da-zA-Z _-]#", " ", $userDefinedName);

        return $modifiedName;
    }

    /**
     * Moves the file from temp path to target path
     *
     * @param string $tempPath
     * @param string $targetPath
     * @throws Exception
     */
    private function moveFromTemp(string $tempPath, string $targetPath): void
    {
        $isMoved = @rename($tempPath, $targetPath);
        if (!$isMoved) {
            $lastError = json_encode(error_get_last(), JSON_PRETTY_PRINT);
            $message = "
                        Could not move the file from temp path: {$tempPath} to target folder ($targetPath).
                        Got error {$lastError}
                    ";
            throw new Exception($message);
        }
    }

    /**
     * Will handle situation where the {@see UploadedFile} entity save was not successful
     *
     * @param string $targetPath
     */
    private function handleUnsavedEntity(string $targetPath): void
    {
        $message   = "Entity saving failed.";
        $isRemoved = unlink($targetPath);
        if (!$isRemoved) {
            $message .= "Could not remove the file: {$targetPath}.";
        }

        $this->logger->critical($message);
    }

}
