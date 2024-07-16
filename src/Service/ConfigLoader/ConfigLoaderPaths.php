<?php

namespace App\Service\ConfigLoader;

/**
 * Contains paths configuration
 */
class ConfigLoaderPaths
{

    /**
     * @var string $databaseBackupFolderPath
     */
    private string $databaseBackupFolderPath;

    /**
     * @var string $projectConfigurationBackupFolderPath
     */
    private string $projectConfigurationBackupFolderPath;

    /**
     * @return string
     */
    public function getDatabaseBackupFolderPath(): string
    {
        return $this->databaseBackupFolderPath;
    }

    /**
     * @param string $databaseBackupFolderPath
     */
    public function setDatabaseBackupFolderPath(string $databaseBackupFolderPath): void
    {
        $this->databaseBackupFolderPath = $databaseBackupFolderPath;
    }

    /**
     * @return string
     */
    public function getProjectConfigurationBackupFolderPath(): string
    {
        return $this->projectConfigurationBackupFolderPath;
    }

    /**
     * @param string $projectConfigurationBackupFolderPath
     */
    public function setProjectConfigurationBackupFolderPath(string $projectConfigurationBackupFolderPath): void
    {
        $this->projectConfigurationBackupFolderPath = $projectConfigurationBackupFolderPath;
    }

}