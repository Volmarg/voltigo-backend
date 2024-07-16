<?php

namespace App\Service\File;

use App\DTO\Internal\Upload\UploadConfigurationDTO;
use App\Exception\File\UploadValidationException;
use App\Service\Security\Scanner\FileScannerService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles validating the provided files
 * See also:
 * {@link https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload}
 */
class FileUploadValidator implements FileUploadValidatorInterface
{
    private float $handledFileSizeMb;

    public function __construct(
        private readonly LoggerInterface $logger
    )
    {}

    /**
     * Will initialize properties / configuration.
     * This is a must due to:
     * - {@see FileUploadValidator::preSaveValidation()} - temporary file still exists,
     * - {@see FileUploadValidator::preSaveValidation()} - temporary file no longer exists,
     *
     * @param UploadedFile $uploadedFile
     * @param float $frontendFileSizeBytes
     *
     * @return void
     */
    public function init(UploadedFile $uploadedFile, float $frontendFileSizeBytes): void
    {
        $this->handledFileSizeMb = $this->getFileSize($uploadedFile, $frontendFileSizeBytes);
    }

    /**
     * If provided file passes the set of checks then it can be uploaded"," else file will be removed from temp folder","
     * and exception will be thrown
     *
     * @param UploadedFile           $uploadedFile
     * @param UploadConfigurationDTO $uploadConfiguration
     *
     * @throws UploadValidationException
     */
    public function preUploadValidation(UploadedFile $uploadedFile, UploadConfigurationDTO $uploadConfiguration): void
    {
        if (!$this->isTransferredFileSizeValid($uploadConfiguration)) {
            $maxSizeMb  = $uploadConfiguration->getMaxFileSizeMb();

            $this->handleInvalidFile($uploadedFile);
            throw new UploadValidationException("Invalid file size. Max is: {$maxSizeMb} got: {$this->handledFileSizeMb}");
        }

        if (!$this->isExtensionValid($uploadedFile, $uploadConfiguration)) {
            $this->handleInvalidFile($uploadedFile);

            $fileExtension     = $uploadedFile->getExtension();
            $allowedExtensions = json_encode($uploadConfiguration->getAllowedExtensions());
            $deniedExtensions  = json_encode(FileUploadValidatorInterface::GLOBALLY_DENIED_FILE_EXTENSIONS);
            $message           = "
                Invalid extension:
                - got: {$fileExtension},
                - allowed are: {$allowedExtensions},
                - denied are: {$deniedExtensions}
            ";
            throw new UploadValidationException($message);
        }

        if (!$this->isMimeValid($uploadedFile, $uploadConfiguration)) {
            $this->handleInvalidFile($uploadedFile);

            $fileMimeType     = $uploadedFile->getMimeType();
            $allowedMimeTypes = json_encode($uploadConfiguration->getAllowedMimeTypes());
            $deniedMimeTypes  = json_encode(FileUploadValidatorInterface::GLOBALLY_DENIED_MIME_TYPES);
            $message           = "
                Invalid mime type:
                - got: {$fileMimeType},
                - allowed are: {$allowedMimeTypes},
                - denied are: {$deniedMimeTypes}
            ";
            throw new UploadValidationException($message);
        }

        if (!$this->isNameValid($uploadedFile)) {
            $this->handleInvalidFile($uploadedFile);

            // prevent the malicious code to somehow be inserted in logger logic
            throw new UploadValidationException("Provided file name is not valid. Cannot log the name for safety reasons!");
        }
    }

    /**
     * Provides set of validation right before the file is about to be saved
     * - is uploaded file size proper (something could go wrong with file saving),
     *
     * @param string $targetFilePath
     *
     * @throws UploadValidationException
     */
    public function preSaveValidation(string $targetFilePath)
    {
        $allowedFileSizeDiffPercent = 30;
        $currentFileSizeMb          = strlen(file_get_contents($targetFilePath)) / 1024 / 1024;
        $minExpectedFileSize        = ($this->handledFileSizeMb - $allowedFileSizeDiffPercent / 100 * $this->handledFileSizeMb);

        if ($currentFileSizeMb < $minExpectedFileSize) {
            $message = "
                Size of the file saved in target path: {$targetFilePath}, is incorrect.
                - Real file size is: {$this->handledFileSizeMb} Mb,
                - Expected min. size is: ~{$minExpectedFileSize} Mb,
                - Got size of file in target path: {$currentFileSizeMb}, 
            ";
            $isRemoved = @unlink($targetFilePath);
            if (!$isRemoved) {
                $this->logger->critical("Could not remove the file: {$targetFilePath}, please remove it manually. Possible error: " . json_encode(error_get_last()));
            }

            throw new UploadValidationException($message);
        }
    }

    /**
     * @param UploadConfigurationDTO $uploadConfiguration
     *
     * @return bool
     */
    private function isTransferredFileSizeValid(UploadConfigurationDTO $uploadConfiguration): bool
    {
        return !($this->handledFileSizeMb > $uploadConfiguration->getMaxFileSizeMb());
    }

    /**
     * @param UploadedFile           $uploadedFile
     * @param UploadConfigurationDTO $uploadConfiguration
     *
     * @return bool
     */
    private function isExtensionValid(UploadedFile $uploadedFile, UploadConfigurationDTO $uploadConfiguration): bool
    {
        return (
                !in_array($uploadedFile->getExtension(), FileUploadValidatorInterface::GLOBALLY_DENIED_FILE_EXTENSIONS)
            ||  in_array($uploadedFile->getExtension(), $uploadConfiguration->getAllowedExtensions())
        );
    }

    /**
     * @param UploadedFile           $uploadedFile
     * @param UploadConfigurationDTO $uploadConfiguration
     *
     * @return bool
     */
    private function isMimeValid(UploadedFile $uploadedFile, UploadConfigurationDTO $uploadConfiguration): bool
    {
        return (
                !in_array($uploadedFile->getMimeType(), FileUploadValidatorInterface::GLOBALLY_DENIED_MIME_TYPES)
            &&  in_array($uploadedFile->getMimeType(), $uploadConfiguration->getAllowedMimeTypes())
        );
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return bool
     */
    private function isNameValid(UploadedFile $uploadedFile): bool
    {
        if (preg_match("#" . FileUploadValidatorInterface::MULTI_EXTENSION_REGEXP . "#", $uploadedFile->getClientOriginalName())) {
            return false;
        }

        if (preg_match("#" . FileUploadValidatorInterface::DISALLOWED_CHARACTERS_REGEXP . "#", $uploadedFile->getClientOriginalName())) {
            return false;
        }

        return true;
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    private function handleInvalidFile(UploadedFile $uploadedFile): void
    {
        $isRemoved = @unlink($uploadedFile->getPathname());
        if (!$isRemoved) {
            $dataBag = [
                "error" => error_get_last(),
                ... $this->buildSafeFileLogData($uploadedFile)
            ];
            $this->logger->critical("Could not remove invalid uploaded file", $dataBag);
        }
    }

    /**
     * Will build an array which consist information about file that was upload but something is wrong with that file.
     * For safety reasons it's not allowed to log full name, as it might contain malicious code.
     *
     * Providing as much data as it's possible to locate the file,
     *
     * This should be only used as fallback when something goes wrong with file removal etc.
     *
     * @param UploadedFile $uploadedFile
     *
     * @return array
     */
    private function buildSafeFileLogData(UploadedFile $uploadedFile): array
    {
        $fileNameLength     = strlen($uploadedFile->getPathname());
        $uploadDate         = (new DateTime())->format("Y-m-d H:i:s");
        $usedNamePartLength = ceil(ceil($fileNameLength / 2) / 5);
        $fileName           = substr($usedNamePartLength, 1, $usedNamePartLength)
                            . "..."
                            . substr($usedNamePartLength, -1, $usedNamePartLength);

        return [
            "uploadDate" => $uploadDate,
            "fileName"   => $fileName,
        ];
    }

    /**
     * Returns the file size in Mb
     *
     * So why using the frontend size as it's not to be trusted?
     *
     * There is some severe issue with php / linux showing the uploaded file size (in temp) when using stat or php functions.
     * Yet when the file is moved outside the temp dir, then the size is calculated correctly.
     *
     * Moving the file directly from temp is NOT AN OPTION: {@see FileScannerService}
     *
     * Could not find any working solution for that on backend, so decided to take the size from front.
     * Then checking if the file size from front is smaller than allowed max from configuration.
     *
     * If file size will somehow be malformed on front to appear smaller, then still backend validation is triggered,
     * so even if front size is small, backend can still react.
     *
     * Not all the file sizes are incorrect on backend - unknown why, but by all means this is some way to prevent,
     * front based malformation
     *
     * @param UploadedFile $uploadedFile
     * @param float        $frontendFileSize
     *
     * @return float
     */
    private function getFileSize(UploadedFile $uploadedFile, float $frontendFileSize): float
    {
        $frontSizeMb       = round(($frontendFileSize / 1024 / 1024), 2);
        $backendFileSizeMb = round(($uploadedFile->getSize() / 1024 / 1024), 2);
        $usedSize          = $frontSizeMb;
        if ($frontSizeMb < $backendFileSizeMb) {
            $usedSize = $backendFileSizeMb;
            $this->logger->critical("Seems like someone tries to manipulate the front file size!", [
                "frontFileSize"    => $frontSizeMb,
                "backFileSize"     => $backendFileSizeMb,
                "originalFileName" => $uploadedFile->getClientOriginalName(),
            ]);
        }

        return $usedSize;
    }
}