<?php

namespace App\Service\File;

use App\DTO\Internal\File\TemporaryFileDTO;
use App\Service\Api\FinancesHub\FinancesHubService;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Handles all the logic related to saving files to temporary dir:
 * - for upload use explicitly {@see FileUploadService} as the uploaded files must be validated
 *
 * This service should only be used for trusted sources like for example getting invoices from {@see FinancesHubService}
 */
class TemporaryFileHandlerService
{

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ){}

    /**
     * It's basically taking file content and saving it as some random temporary file
     *
     * @param string $fileContent
     * @param string $extension
     *
     * @return TemporaryFileDTO
     */
    public function saveFile(string $fileContent, string $extension): TemporaryFileDTO
    {
        $tmpFileDto = new TemporaryFileDTO();

        $fileName            = uniqid("invoice") . ".{$extension}";
        $tmpDirFolder        = $this->parameterBag->get('tmp.dir');
        $tmpFileAbsolutePath = $tmpDirFolder . DIRECTORY_SEPARATOR . $fileName;

        $this->validateTmpDir($tmpDirFolder);
        $result = @file_put_contents($tmpFileAbsolutePath, $fileContent);
        if (is_bool($result)) {
            $possibleError = json_encode(error_get_last(), JSON_PRETTY_PRINT);
            throw new LogicException("Failed saving the file under path: {$tmpFileAbsolutePath}. Maybe related error: {$possibleError}");
        }

        $this->validateTmpFile($tmpFileAbsolutePath);

        $tmpFileDto->setAbsoluteFilePath($tmpFileAbsolutePath);
        $tmpFileDto->setFileName($fileName);

        return $tmpFileDto;
    }

    /**
     * @param string $tmpDirFolder
     */
    private function validateTmpDir(string $tmpDirFolder): void
    {
        if (!file_exists($tmpDirFolder)) {
            throw new LogicException("Temp dir does not exist: {$tmpDirFolder}");
        }

        if (!is_dir($tmpDirFolder)) {
            throw new LogicException("Temp dir is actually not a folder: {$tmpDirFolder}");
        }

        if (!is_writable($tmpDirFolder)) {
            throw new LogicException("Temp dir is not writable: {$tmpDirFolder}");
        }
    }

    /**
     * @param string $tmpFileAbsolutePath
     */
    private function validateTmpFile(string $tmpFileAbsolutePath): void
    {
        if (!file_exists($tmpFileAbsolutePath)) {
            throw new LogicException("Temp file does not exist under path: {$tmpFileAbsolutePath}");
        }
    }

}