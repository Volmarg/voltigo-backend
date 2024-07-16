<?php

namespace App\Controller\Core;

use App\Service\ConfigLoader\ConfigLoaderJobOffer;
use App\Service\ConfigLoader\ConfigLoaderPaths;
use App\Service\ConfigLoader\ConfigLoaderProject;
use App\Service\ConfigLoader\ConfigLoaderWebSocket;
use App\Service\ConfigLoader\SecurityConfigLoader;
use App\Service\ConfigLoader\StorageConfigLoader;

/**
 * Consist of all config loaders
 *
 * Class ConfigLoader
 * @package App\Controller\Core
 */
class ConfigLoader
{

    /**
     * @var SecurityConfigLoader $securityConfigLoader
     */
    private SecurityConfigLoader $securityConfigLoader;

    /**
     * @var StorageConfigLoader $storageConfigLoader
     */
    private StorageConfigLoader $storageConfigLoader;

    /**
     * @var ConfigLoaderProject $configLoaderProject
     */
    private ConfigLoaderProject $configLoaderProject;

    /**
     * @var ConfigLoaderPaths $configLoaderPaths
     */
    private ConfigLoaderPaths $configLoaderPaths;

    /**
     * @var ConfigLoaderWebSocket $configLoaderWebSocket
     */
    private ConfigLoaderWebSocket $configLoaderWebSocket;

    /**
     * @var ConfigLoaderJobOffer $configLoaderJobOffer
     */
    private ConfigLoaderJobOffer $configLoaderJobOffer;

    /**
     * @return ConfigLoaderJobOffer
     */
    public function getConfigLoaderJobOffer(): ConfigLoaderJobOffer
    {
        return $this->configLoaderJobOffer;
    }

    /**
     * @param ConfigLoaderJobOffer $configLoaderJobOffer
     */
    public function setConfigLoaderJobOffer(ConfigLoaderJobOffer $configLoaderJobOffer): void
    {
        $this->configLoaderJobOffer = $configLoaderJobOffer;
    }

    /**
     * @return StorageConfigLoader
     */
    public function getStorageConfigLoader(): StorageConfigLoader
    {
        return $this->storageConfigLoader;
    }

    /**
     * @param StorageConfigLoader $storageConfigLoader
     */
    public function setStorageConfigLoader(StorageConfigLoader $storageConfigLoader): void
    {
        $this->storageConfigLoader = $storageConfigLoader;
    }

    /**
     * @return SecurityConfigLoader
     */
    public function getSecurityConfigLoader(): SecurityConfigLoader
    {
        return $this->securityConfigLoader;
    }

    /**
     * @param SecurityConfigLoader $securityConfigLoader
     */
    public function setSecurityConfigLoader(SecurityConfigLoader $securityConfigLoader): void
    {
        $this->securityConfigLoader = $securityConfigLoader;
    }

    /**
     * @return ConfigLoaderProject
     */
    public function getConfigLoaderProject(): ConfigLoaderProject
    {
        return $this->configLoaderProject;
    }

    /**
     * @param ConfigLoaderProject $configLoaderProject
     */
    public function setConfigLoaderProject(ConfigLoaderProject $configLoaderProject): void
    {
        $this->configLoaderProject = $configLoaderProject;
    }

    /**
     * @return ConfigLoaderPaths
     */
    public function getConfigLoaderPaths(): ConfigLoaderPaths
    {
        return $this->configLoaderPaths;
    }

    /**
     * @param ConfigLoaderPaths $configLoaderPaths
     */
    public function setConfigLoaderPaths(ConfigLoaderPaths $configLoaderPaths): void
    {
        $this->configLoaderPaths = $configLoaderPaths;
    }

    /**
     * @return ConfigLoaderWebSocket
     */
    public function getConfigLoaderWebSocket(): ConfigLoaderWebSocket
    {
        return $this->configLoaderWebSocket;
    }

    /**
     * @param ConfigLoaderWebSocket $configLoaderWebSocket
     */
    public function setConfigLoaderWebSocket(ConfigLoaderWebSocket $configLoaderWebSocket): void
    {
        $this->configLoaderWebSocket = $configLoaderWebSocket;
    }

}