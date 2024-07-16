<?php

namespace App\Controller\Storage;

use App\Entity\Storage\BannedJwtTokenStorage;
use App\Repository\Storage\BannedJwtTokenStorageRepository;
use App\Service\Security\JwtAuthenticationService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class BannedJwtTokenStorageController
{
    /**
     * @var BannedJwtTokenStorageRepository $bannedJwtTokenStorageRepository
     */
    private BannedJwtTokenStorageRepository $bannedJwtTokenStorageRepository;

    /**
     * @var JwtAuthenticationService $jwtAuthenticationService
     */
    private JwtAuthenticationService $jwtAuthenticationService;

    /**
     * @param BannedJwtTokenStorageRepository $bannedJwtTokenStorageRepository
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function __construct(BannedJwtTokenStorageRepository $bannedJwtTokenStorageRepository, JwtAuthenticationService $jwtAuthenticationService)
    {
        $this->jwtAuthenticationService        = $jwtAuthenticationService;
        $this->bannedJwtTokenStorageRepository = $bannedJwtTokenStorageRepository;
    }

    /**
     * Will build {@see BannedJwtTokenStorage} entity from raw token and will then persist it
     *
     * @throws JWTDecodeFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveJwtTokenAsBannedTokenForRequest(): void
    {
        $jwtToken = $this->jwtAuthenticationService->extractJwtFromRequest();
        if( empty($jwtToken) ){
            return;
        }
        $expirationTimestamp = $this->jwtAuthenticationService->getTokenExpirationTimestamp($jwtToken);

        $bannedToken = new BannedJwtTokenStorage();
        $bannedToken->setToken($jwtToken);
        $bannedToken->setTokenExpirationTimestamp($expirationTimestamp);

        $this->bannedJwtTokenStorageRepository->save($bannedToken);
    }

    /**
     * Will check if given token is banned
     *
     * @param string $jwtToken
     * @return bool
     */
    public function isBanned(string $jwtToken): bool
    {
        $bannedToken = $this->bannedJwtTokenStorageRepository->findByToken($jwtToken);
        return !empty($bannedToken);
    }

}