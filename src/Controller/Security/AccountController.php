<?php

namespace App\Controller\Security;

use App\Entity\Ecommerce\Account\Account;
use App\Entity\Ecommerce\Account\AccountType;
use App\Entity\Security\User;
use App\Repository\Ecommerce\Account\AccountRepository;
use App\Repository\Ecommerce\Account\AccountTypeRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;

class AccountController
{

    /**
     * @var AccountRepository $accountRepository
     */
    private AccountRepository $accountRepository;

    /**
     * @var AccountTypeRepository $accountTypeRepository
     */
    private AccountTypeRepository $accountTypeRepository;

    /**
     * @param AccountRepository $accountRepository
     * @param AccountTypeRepository $accountTypeRepository
     */
    public function __construct(AccountRepository $accountRepository, AccountTypeRepository $accountTypeRepository)
    {
        $this->accountRepository     = $accountRepository;
        $this->accountTypeRepository = $accountTypeRepository;
    }

    /**
     * @param User $user
     * @return Account
     * @throws Exception
     */
    public function buildAccountOfFreeType(User $user): Account
    {
        $accountType = $this->findAccountTypeForName(AccountType::TYPE_FREE);
        $account = new Account();

        $user->setAccount($account);

        $account->setUser($user);
        $account->setType($accountType);

        return $account;
    }

    /**
     * Will return one account type entity by account type name
     *
     * @param string $accountTypeName
     * @return AccountType
     * @throws Exception
     */
    public function findAccountTypeForName(string $accountTypeName): AccountType
    {
        $accountType = $this->accountTypeRepository->findOneByAccountTypeName($accountTypeName);
        if( empty($accountType) ){
            throw new Exception("Account type of given name does not exist in database: " . $accountTypeName);
        }

        return $accountType;
    }

    /**
     * Will create or update existing entity
     *
     * @param Account $account
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveAccount(Account $account): void
    {
        $this->accountRepository->saveAccount($account);
    }
}