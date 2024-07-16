<?php

namespace App\Security;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * This csrf token manager logic was mostly copied from the original manager
 * since it's logic is generally fine, but had to be adjusted a bit to work
 * without namespaced id.
 *
 * Class CsrfTokenManager
 * @package App\Security
 */
class CsrfTokenManager implements CsrfTokenManagerInterface
{
    /**
     * @var TokenGeneratorInterface $generator
     */
    private TokenGeneratorInterface $generator;
    /**
     * @var NativeSessionTokenStorage|TokenStorageInterface $storage
     */
    private TokenStorageInterface|NativeSessionTokenStorage $storage;

    /**
     * CsrfTokenManager constructor.
     * @param TokenGeneratorInterface|null $generator
     * @param CsrfTokenStorage $storage
     */
    public function __construct(CsrfTokenStorage $storage, TokenGeneratorInterface $generator = null)
    {
        $this->generator = $generator ?? new UriSafeTokenGenerator();
        $this->storage   = $storage;
    }

    /**
     * @param string $tokenId
     * @return CsrfToken
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getToken(string $tokenId): CsrfToken
    {

        if ($this->storage->hasToken($tokenId)) {
            $value = $this->storage->getToken($tokenId);
        } else {
            $value = $this->generator->generateToken();
            $this->storage->setToken($tokenId, $value);
        }

        return new CsrfToken($tokenId, $this->randomize($value));
    }

    /**
     * @param string $tokenId
     * @return CsrfToken
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function refreshToken(string $tokenId): CsrfToken
    {
        $value = $this->generator->generateToken();
        $this->storage->setToken($tokenId, $value);

        return new CsrfToken($tokenId, $this->randomize($value));
    }

    /**
     * @param string $tokenId
     * @return string|null
     * @throws ORMException
     */
    public function removeToken(string $tokenId): ?string
    {
        return $this->storage->removeToken($tokenId);
    }

    /**
     * @param CsrfToken $token
     * @return bool
     */
    public function isTokenValid(CsrfToken $token): bool
    {
        if (!$this->storage->hasToken($token->getId())) {
            return false;
        }

        return hash_equals($this->storage->getToken($token->getId()), $this->derandomize($token->getValue()));
    }

    /**
     * @param string $value
     * @return string
     * @throws Exception
     */
    private function randomize(string $value): string
    {
        $key = random_bytes(32);
        $value = $this->xor($value, $key);

        return sprintf('%s.%s.%s', substr(md5($key), 0, 1 + (\ord($key[0]) % 32)), rtrim(strtr(base64_encode($key), '+/', '-_'), '='), rtrim(strtr(base64_encode($value), '+/', '-_'), '='));
    }

    /**
     * @param string $value
     * @return string
     */
    private function derandomize(string $value): string
    {
        $parts = explode('.', $value);
        if (3 !== \count($parts)) {
            return $value;
        }
        $key = base64_decode(strtr($parts[1], '-_', '+/'));
        $value = base64_decode(strtr($parts[2], '-_', '+/'));

        return $this->xor($value, $key);
    }

    /**
     * @param string $value
     * @param string $key
     * @return string
     */
    private function xor(string $value, string $key): string
    {
        if (\strlen($value) > \strlen($key)) {
            $repeatNumber = (int)ceil(\strlen($value) / \strlen($key));
            $key = str_repeat($key, $repeatNumber);
        }

        return $value ^ $key;
    }
}