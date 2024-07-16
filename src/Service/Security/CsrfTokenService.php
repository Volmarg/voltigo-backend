<?php

namespace App\Service\Security;

use App\Security\CsrfTokenManager;
use App\Service\Logger\LoggerService;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Service for handling logic related to CSRF
 *
 * Class CsrfTokenService
 * @package App\Service\Security
 */
class CsrfTokenService
{
    // csrf keys names must be synchronised with front and MUST be lowercase
    const KEY_CSRF_TOKEN    = "token";
    const KEY_CSRF_TOKEN_ID = "csrftokenid";

    /**
     * @var CsrfTokenManager $csrfTokenManager
     */
    private CsrfTokenManager $csrfTokenManager;

    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;

    /**
     * CsrfTokenService constructor.
     *
     * @param CsrfTokenManager $csrfTokenManager
     * @param LoggerService $loggerService
     */
    public function __construct(CsrfTokenManager $csrfTokenManager, LoggerService $loggerService)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->loggerService    = $loggerService;
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string $id The id used when generating the token
     * @param string $token The actual token sent with the request that should be validated
     * @throws ORMException
     */
    public function isCsrfTokenValid(string $id, string $token): bool
    {
        $isTokenValid = $this->csrfTokenManager->isTokenValid(new CsrfToken($id, $token));
        if(!$isTokenValid){
            $this->loggerService->warning("Got invalid csrf token", [
                "id"    => $id,
                "token" => $token,
            ]);
        }

        if($isTokenValid){
            $this->csrfTokenManager->removeToken($id);
        }

        return $isTokenValid;
    }

}