<?php

namespace App\Service\ConfigLoader;

/**
 * Contain project related information
 */
class ConfigLoaderProject
{

    /**
     * @var string $projectName
     */
    private string $projectName;

    /**
     * @var string $fromMail
     */
    private string $fromMail;

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->projectName;
    }

    /**
     * @param string $projectName
     */
    public function setProjectName(string $projectName): void
    {
        $this->projectName = $projectName;
    }

    /**
     * @return string
     */
    public function getFromMail(): string
    {
        return $this->fromMail;
    }

    /**
     * @param string $fromMail
     */
    public function setFromMail(string $fromMail): void
    {
        $this->fromMail = $fromMail;
    }

}