<?php

namespace App\Service\ConfigLoader;

/**
 * Handles loading security based configuration
 *
 * Class SecurityConfigLoader
 * @package App\Service\ConfigLoader
 */
class SecurityConfigLoader
{
    /**
     * @var int $jwtTokenLifetime
     */
    private int $jwtTokenLifetime;

    /**
     * @var string $frontendEncryptionPrivateKey
     */
    private string $frontendEncryptionPrivateKey;

    /**
     * @return int
     */
    public function getJwtTokenLifetime(): int
    {
        return $this->jwtTokenLifetime;
    }

    /**
     * @param int $jwtTokenLifetime
     */
    public function setJwtTokenLifetime(int $jwtTokenLifetime): void
    {
        $this->jwtTokenLifetime = $jwtTokenLifetime;
    }

    /**
     * @return string
     */
    public function getFrontendEncryptionPrivateKey(): string
    {
        return $this->frontendEncryptionPrivateKey;
    }

    /**
     * @param string $frontendEncryptionPrivateKeyPath
     */
    public function setFrontendEncryptionPrivateKey(string $frontendEncryptionPrivateKeyPath): void
    {
        $this->frontendEncryptionPrivateKey = file_get_contents($frontendEncryptionPrivateKeyPath);
    }

}