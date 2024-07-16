<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\Storage\BannedJwtTokenStorageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * This entity stores jwt tokens that for whatever reason were banned
 * These tokens are stored till they expire
 *
 * @ORM\Entity(repositoryClass=BannedJwtTokenStorageRepository::class)
 */
class BannedJwtTokenStorage implements StorageInterface, EntityInterface
{
    const FIELD_NAME_TOKEN = "token";

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
     * - This field cannot be encrypted because it's used in queries,
     * - That's also ok because there is {@see OneTimeJwtTokenStorageCleanupService} which removes old entries
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private string $token = "";

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $tokenExpirationTimestamp = "";

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
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getTokenExpirationTimestamp(): string
    {
        return $this->tokenExpirationTimestamp;
    }

    /**
     * @param string $tokenExpirationTimestamp
     */
    public function setTokenExpirationTimestamp(string $tokenExpirationTimestamp): void
    {
        $this->tokenExpirationTimestamp = $tokenExpirationTimestamp;
    }

}
