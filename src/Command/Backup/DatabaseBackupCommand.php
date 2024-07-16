<?php

namespace App\Command\Backup;

use Exception;
use TypeError;

/**
 * Will create database backup
 *
 * Class CleanupStoragesCommand
 */
class DatabaseBackupCommand extends BaseBackupCommand
{
    const COMMAND_NAME          = "backup:database";
    const DUMP_FILE_NAME_PREFIX = "database_backup_";
    const SQL_EXTENSION         = "sql";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will create database backup");
    }

    /**
     * Execute the command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        $this->io->info("Started creating database backup");
        {
            try{
                $defaultBackupPath = $this->configLoader->getConfigLoaderPaths()->getDatabaseBackupFolderPath();
                $usedDumpFolder    = $this->handleTargetFolder($defaultBackupPath);

                $fileName                 = self::DUMP_FILE_NAME_PREFIX . (new \DateTime())->format("Y_m_d_H_i_s") . ".";
                $fileNameWithSqlExtension = $fileName . self::SQL_EXTENSION;
                $sqlDumpFullPath          = $usedDumpFolder . DIRECTORY_SEPARATOR . $fileNameWithSqlExtension;

                $process = $this->services->getShellService()->getShellMysqlDumpService()->dumpDatabase($sqlDumpFullPath);
                $this->checkBackupProcessAndCreateArchive([$sqlDumpFullPath], $fileName, $usedDumpFolder, $process);

                // no longer needed as got archive now
                unlink($sqlDumpFullPath);
            }catch(Exception | TypeError $e){
                $this->services->getLoggerService()->logException($e);
                $this->io->error("Exception was thrown while doing database backup");
                return self::FAILURE;
            }
        }

        $this->io->success("Finished creating database backup");
        return self::SUCCESS;
    }

}