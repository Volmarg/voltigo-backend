<?php

namespace App\Command\Backup;

use Exception;
use TypeError;

/**
 * Will create database backup
 *
 * Class CleanupStoragesCommand
 */
class SystemConfigurationBackupCommand extends BaseBackupCommand
{
    const COMMAND_NAME          = "backup:system-configuration";
    const DUMP_FILE_NAME_PREFIX = "project_configuration_backup_";

    const ENV_FILE_PATH_TOWARD_PROJECT_ROOT      = ".env";
    const CONFIG_FOLDER_PATH_TOWARD_PROJECT_ROOT = "config";

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
        $this->io->info("Started creating project configuration backup");
        {
            try{

                $defaultBackupPath = $this->configLoader->getConfigLoaderPaths()->getProjectConfigurationBackupFolderPath();
                $usedDumpFolder    = $this->handleTargetFolder($defaultBackupPath);
                $dumpFilePath      = $usedDumpFolder. DIRECTORY_SEPARATOR . self::DUMP_FILE_NAME_PREFIX . (new \DateTime())->format("Y_m_d_H_i_s");

                $projectRotDirAbsolutePath = $this->parameterBag->get("kernel.project_dir");
                $configFolderAbsolutePath  = $projectRotDirAbsolutePath . DIRECTORY_SEPARATOR . self::CONFIG_FOLDER_PATH_TOWARD_PROJECT_ROOT;
                $envFileAbsolutePath       = $projectRotDirAbsolutePath . DIRECTORY_SEPARATOR . self::ENV_FILE_PATH_TOWARD_PROJECT_ROOT;

                $filesPathsToArchive = [
                    $configFolderAbsolutePath,
                    $envFileAbsolutePath,
                ];

                $this->checkBackupProcessAndCreateArchive(
                    $filesPathsToArchive,
                    self::DUMP_FILE_NAME_PREFIX,
                    $dumpFilePath,
                );
            }catch(Exception | TypeError $e){
                $this->services->getLoggerService()->logException($e);
                $this->io->error("Exception was thrown while doing project configuration backup");
                return self::FAILURE;
            }
        }

        $this->io->success("Finished creating project configuration backup");
        return self::SUCCESS;
    }

}