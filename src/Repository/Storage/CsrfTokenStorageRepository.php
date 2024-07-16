<?php

namespace App\Repository\Storage;

use App\Entity\Storage\CsrfTokenStorage;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CsrfTokenStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsrfTokenStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsrfTokenStorage[]    findAll()
 * @method CsrfTokenStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsrfTokenStorageRepository extends AbstractStorageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsrfTokenStorage::class);
    }

    /**
     * Will find one token by the token in
     *
     * @param string $tokenId
     * @return CsrfTokenStorage|null
     */
    public function findByTokenId(string $tokenId): ?CsrfTokenStorage
    {
        return $this->findOneBy([
            CsrfTokenStorage::FIELD_NAME_TOKEN_ID => $tokenId,
        ]);
    }

    /**
     * Will remove the token for token id
     *
     * @param string $tokenId
     * @return string|null Returns the removed token if one existed, NULL
     *                     otherwise
     * @throws ORMException
     */
    public function removeByTokenId(string $tokenId): ?string
    {
        $tokenEntity = $this->findByTokenId($tokenId);
        if( empty($tokenEntity) ){
            return null;
        }

        $token = $tokenEntity->getGeneratedToken();
        $this->_em->remove($tokenEntity);
        $this->_em->flush();

        return $token;
    }

}
