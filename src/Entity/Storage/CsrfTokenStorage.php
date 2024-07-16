<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\Storage\CsrfTokenStorageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CsrfTokenStorageRepository::class)
 */
class CsrfTokenStorage implements StorageInterface, EntityInterface
{
    const FIELD_NAME_TOKEN_ID = "tokenId";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $tokenId = "";

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $generatedToken = "";

    public function __construct()
    {
        $this->created = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string
     */
    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    /**
     * @param string $tokenId
     */
    public function setTokenId(string $tokenId): void
    {
        $this->tokenId = $tokenId;
    }

    /**
     * @return string
     */
    public function getGeneratedToken(): string
    {
        return $this->generatedToken;
    }

    /**
     * @param string $generatedToken
     */
    public function setGeneratedToken(string $generatedToken): void
    {
        $this->generatedToken = $generatedToken;
    }

}
