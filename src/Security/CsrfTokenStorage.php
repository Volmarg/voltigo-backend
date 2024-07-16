<?php

namespace App\Security;

use App\Entity\Storage\CsrfTokenStorage as CsrfTokenStorageEntity;
use App\Repository\Storage\CsrfTokenStorageRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Handles storing the csrf tokens in DB
 * this is a MUST because sessions are not active since backend serves as API
 *
 * Class CsrfTokenStorage
 * @package App\Security
 */
class CsrfTokenStorage implements TokenStorageInterface
{
    /**
     * @var CsrfTokenStorageRepository $csrfTokenStorageRepository
     */
    private CsrfTokenStorageRepository $csrfTokenStorageRepository;

    public function __construct(CsrfTokenStorageRepository $csrfTokenStorageRepository)
    {
        $this->csrfTokenStorageRepository = $csrfTokenStorageRepository;
    }

    /**
     * Reads a stored CSRF token.
     *
     * @return string The stored token
     *
     * @throws TokenNotFoundException If the token ID does not exist
     */
    public function getToken(string $tokenId): string
    {
        $csrfTokenEntity = $this->csrfTokenStorageRepository->findByTokenId($tokenId);
        if( empty($csrfTokenEntity) ){
            throw new TokenNotFoundException("No csrf token has been found in DB for token id: {$tokenId}");
        }

        return $csrfTokenEntity->getGeneratedToken();
    }

    /**
     * Stores a CSRF token.
     *
     * @param string $tokenId
     * @param string $token
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function setToken(string $tokenId, string $token): void
    {
        $csrfTokenEntity = new CsrfTokenStorageEntity();
        $csrfTokenEntity->setTokenId($tokenId);
        $csrfTokenEntity->setGeneratedToken($token);

        $this->csrfTokenStorageRepository->save($csrfTokenEntity);
    }

    /**
     * Removes a CSRF token.
     *
     * @return string|null Returns the removed token if one existed, NULL
     *                     otherwise
     * @throws ORMException
     */
    public function removeToken(string $tokenId): ?string
    {
        return $this->csrfTokenStorageRepository->removeByTokenId($tokenId);
    }

    /**
     * Checks whether a token with the given token ID exists.
     *
     * @return bool Whether a token exists with the given ID
     */
    public function hasToken(string $tokenId): bool
    {
        return !empty($this->csrfTokenStorageRepository->findByTokenId($tokenId));
    }
}