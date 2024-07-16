<?php

namespace App\Repository\File;

use App\Entity\Email\Email;
use App\Entity\Email\EmailTemplate;
use App\Entity\File\UploadedFile;
use App\Entity\Security\User;
use App\Enum\File\UploadedFileSourceEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UploadedFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method UploadedFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method UploadedFile[]    findAll()
 * @method UploadedFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UploadedFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadedFile::class);
    }

    /**
     * @param int $fileId
     * @param User $user
     * @param UploadedFileSourceEnum $fileSourceEnum
     * @return UploadedFile|null
     */
    public function findForUser(int $fileId, User $user, UploadedFileSourceEnum $fileSourceEnum): ?UploadedFile
    {
        return $this->findOneBy([
            "user"   => $user,
            "id"     => $fileId,
            "source" => $fileSourceEnum->name
        ]);
    }

    /**
     * Will return all uploaded files of given source for given user
     *
     * @param User $user
     * @param UploadedFileSourceEnum $fileSourceEnum
     *
     * @return UploadedFile[]
     */
    public function findForUserBySource(User $user, UploadedFileSourceEnum $fileSourceEnum): array
    {
        return $this->findBy([
            'user'   => $user,
            "source" => $fileSourceEnum->name,
        ]);
    }

    /**
     * @param string $path
     * @param string $localName
     *
     * @return UploadedFile|null
     */
    public function findByPublicAccess(string $path, string $localName): ?UploadedFile
    {
        return $this->findOneBy([
            "publicPath"    => $path,
            "localFileName" => $localName,
        ]);
    }

    /**
     * Will return array of deletable {@see UploadedFile} for given user
     *
     * @param User                        $user
     * @param UploadedFileSourceEnum|null $sourceEnum
     *
     * @return UploadedFile[]
     */
    public function findDeletableForUser(User $user, ?UploadedFileSourceEnum $sourceEnum = null): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("uf")
            ->from(UploadedFile::class, "uf")
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in("uf.user", ":user"),
                    $queryBuilder->expr()->eq("uf.deletable", 1)
                )
            )->setParameter("user", $user);

        if ($sourceEnum) {
            $queryBuilder->andWhere("uf.source = :source")
                ->setParameter("source", $sourceEnum->value);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return array of {@see UploadedFile} for matching local file names (these are unique)
     *
     * @param string[] $fileNames
     *
     * @return UploadedFile[]
     */
    public function findByLocalFileNames(array $fileNames): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("uf")
                     ->from(UploadedFile::class, "uf")
                     ->where(
                         $queryBuilder->expr()->in("uf.localFileName", ":fileNames")
                     )->setParameter("fileNames", $fileNames);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Check if the {@see UploadedFile} is used in any {@see Email}
     *
     * @param UploadedFile $uploadedFile
     *
     * @return bool
     */
    public function isReferencedInAnyEmail(UploadedFile $uploadedFile): bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("e")
             ->from(Email::class, "e")
             ->where(
                 $queryBuilder->expr()->like("e.body", ":fileName")
             )
             ->setParameter("fileName", "%{$uploadedFile->getLocalFileName()}%");

        return !empty($queryBuilder->getQuery()->execute());
    }

    /**
     * Check if the {@see UploadedFile} is used in any {@see EmailTemplate}
     *
     * @param UploadedFile $uploadedFile
     *
     * @return bool
     */
    public function isReferencedInAnyEmailTemplate(UploadedFile $uploadedFile): bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("et")
             ->from(EmailTemplate::class, "et")
             ->where(
                 $queryBuilder->expr()->like("et.body", ":fileName")
             )
             ->andWhere("et.deleted = 0")
             ->setParameter("fileName", "%{$uploadedFile->getLocalFileName()}%");

        return !empty($queryBuilder->getQuery()->execute());
    }

}
