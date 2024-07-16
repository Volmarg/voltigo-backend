<?php

namespace App\Repository\Ecommerce\Account;

use App\Entity\Ecommerce\Account\AccountType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccountType|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountType|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountType[]    findAll()
 * @method AccountType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<AccountType>
 */
class AccountTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountType::class);
    }

    /**
     * Will return one account type by its name or null if nothing was found
     *
     * @param string $accountTypeName
     * @return AccountType|null
     */
    public function findOneByAccountTypeName(string $accountTypeName): ?AccountType
    {
        $accountType = $this->findOneBy([
            AccountType::FIELD_NAME => $accountTypeName,
        ]);

        return $accountType;
    }

}
