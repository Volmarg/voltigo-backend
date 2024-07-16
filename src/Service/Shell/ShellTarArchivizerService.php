<?php

namespace App\Service\Shell;

use Exception;

/**
 * Handles archivizing files with tar
 */
class ShellTarArchivizerService extends ShellAbstractService
{
    const EXECUTABLE_BINARY_NAME = "tar";
    const TAR_EXTENSION          = "tar";
    const GZIP_EXTENSION         = "gz";

    /**
     * Allow using absolute file path
     */
    const PARAM_ABSOLUTE_PATH = "P";

    /**
     * Allows setting output file name
     */
    const PARAM_FILENAME = "f";

    /**
     * Use gzip compression additionally
     */
    const PARAM_GZIP = "z";

    /**
     * Do create archive
     */
    const PARAM_CREATE = "c";

    /**
     * Will return executable php binary name
     * @Return string
     */
    protected function getExecutableBinaryName(): string
    {
        return self::EXECUTABLE_BINARY_NAME;
    }

    /**
     * Will build tar archive for given file path
     * - to build archive of file in cwd try "./<fileName>"
     *
     * @param array $filesPaths
     * @param string $outputFileName
     * @param string $backupDirectoryPath
     * @return string - archived file name
     * @throws Exception
     */
    public function buildTarArchiveForTargetFiles(array $filesPaths, string $outputFileName, string $backupDirectoryPath): string
    {
        $archiveFileName  = $this->buildArchiveFileName($outputFileName);
        $archiveFilePath  = $backupDirectoryPath;

        $filesPathsForCommand = array_map(
            fn(string $singleFilePath) => " " . $singleFilePath,
            $filesPaths,
        );

        $commandPartials = [
            " " . self::PARAM_ABSOLUTE_PATH,
            self::PARAM_CREATE,
            self::PARAM_FILENAME,
            self::PARAM_GZIP,
            " " . $archiveFileName,
            ...$filesPathsForCommand,
        ];

        $this->loggerService->info("Now calling tar with partials", $commandPartials);

        /**
         * Changing directory before creating archive is a must to make tar work properly
         * as it does not allow creating archive in specified folder
         */
        $originalDir = getcwd();
        if( !file_exists($backupDirectoryPath) ){
            mkdir($backupDirectoryPath, 0777, true);

        }elseif( !is_writable($backupDirectoryPath)){
            throw new Exception("Target directory exists but is not writable: {$backupDirectoryPath}");
        }

        chdir($backupDirectoryPath);
        {
            $command = $this->buildCommand($commandPartials, false);
            $process = $this->executeShellCommand($command);
        }
        chdir($originalDir);

        if( !$process->isSuccessful() ){
            throw new Exception("Command exited with FAILURE: {$command}");
        }

        return $archiveFilePath;
    }

    /**
     * Will return archive file name
     *
     * @param string $fileName
     * @return string
     */
    private function buildArchiveFileName(string $fileName): string
    {
        $originalFileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        $usedFileName                     = $originalFileNameWithoutExtension . "." . self::TAR_EXTENSION . "." . self::GZIP_EXTENSION;

        return $usedFileName;
    }

}