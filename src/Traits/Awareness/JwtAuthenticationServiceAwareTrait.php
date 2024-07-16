<?php

namespace App\Traits\Awareness;

use App\Exception\EmptyValueException;
use App\Service\Security\JwtAuthenticationService;
use Exception;

/**
 * Supports usage of {@see JwtAuthenticationService}
 */
trait JwtAuthenticationServiceAwareTrait
{
    /**
     * @var JwtAuthenticationService $jwtAuthenticationService
     */
    private JwtAuthenticationService $jwtAuthenticationService;

    /**
     * @return JwtAuthenticationService
     */
    public function getJwtAuthenticationService(): JwtAuthenticationService
    {
        return $this->jwtAuthenticationService;
    }

    /**
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function setJwtAuthenticationService(JwtAuthenticationService $jwtAuthenticationService): void
    {
        $this->jwtAuthenticationService = $jwtAuthenticationService;
    }

    /**
     * Check if the {@see JwtAuthenticationService} is set
     *
     * @throws Exception
     */
    public function assertJwtAuthenticationServiceSet(): void
    {
        $this->jwtAuthenticationService ?? throw new EmptyValueException(JwtAuthenticationService::class . " is not set!");
    }

}















































































