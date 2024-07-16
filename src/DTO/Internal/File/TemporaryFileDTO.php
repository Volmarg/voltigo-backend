<?php

namespace App\DTO\Internal\File;

use App\Service\File\TemporaryFileHandlerService;
use LogicException;

/**
 * {@see TemporaryFileHandlerService}
 */
class TemporaryFileDTO
{
    private string $absoluteFilePath;
    private string $fileName;

    /**
     * @return string
     */
    public function getAbsoluteFilePath(): string
    {
        return $this->absoluteFilePath;
    }

    /**
     * @param string $absoluteFilePath
     */
    public function setAbsoluteFilePath(string $absoluteFilePath): void
    {
        $this->absoluteFilePath = $absoluteFilePath;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * Attempt to remove file from the server if it exists on it
     */
    public function removeFile(): void
    {
        if (!file_exists($this->getAbsoluteFilePath())) {
            return;
        }

        $isRemoved = @unlink($this->getAbsoluteFilePath());
        if (!$isRemoved) {
            $msg = "
                Could not remove file: {$this->getAbsoluteFilePath()}
                Possibly related error: 
            " . json_encode(error_get_last(), JSON_PRETTY_PRINT);
            throw new LogicException($msg);
        }
    }

}