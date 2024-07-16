<?php

namespace App\Controller\Storage;

use App\Entity\Storage\OneTimeJwtTokenStorage;
use App\Repository\Storage\OneTimeJwtTokenStorageRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class OneTimeJwtTokenStorageController
{

    /**
     * @var OneTimeJwtTokenStorageRepository $oneTimeJwtTokenStorageRepository
     */
    private OneTimeJwtTokenStorageRepository $oneTimeJwtTokenStorageRepository;

    /**
     * @param OneTimeJwtTokenStorageRepository $oneTimeJwtTokenStorageRepository
     */
    public function __construct(OneTimeJwtTokenStorageRepository $oneTimeJwtTokenStorageRepository)
    {
        $this->oneTimeJwtTokenStorageRepository = $oneTimeJwtTokenStorageRepository;
    }

    /**
     * Create or update existing token
     *
     * @param OneTimeJwtTokenStorage $oneTimeJwtTokenStorage
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(OneTimeJwtTokenStorage $oneTimeJwtTokenStorage): void
    {
        $this->oneTimeJwtTokenStorageRepository->save($oneTimeJwtTokenStorage);
    }

    /**
     * Will find one token by the token in
     *
     * @param string $token
     * @return bool
     */
    public function isOneTimeTokenAlreadyUsed(string $token): bool
    {
        $token = $this->oneTimeJwtTokenStorageRepository->findByToken($token);
        if( empty($token) ){
            /**
             * Not a one time token or already expired so got removed
             * Regardless in such case access to the page should be denied as
             * otherwise such pages could be open with the token copied from other link etc.
             */
            return true;
        }

        return $token->isUsed();
    }

    /**
     * Will set one time token as expired
     *
     * @param string $token
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function setTokenExpired(string $token): void {
        $oneTimeToken = $this->oneTimeJwtTokenStorageRepository->findByToken($token);
        if( empty($oneTimeToken) ){
            return;
        }

        $oneTimeToken->setUsed(true);;
        $this->save($oneTimeToken);;
    }

}