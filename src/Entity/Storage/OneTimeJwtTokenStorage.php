<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\Storage\OneTimeJwtTokenStorageRepository;
use App\Service\Cleanup\OneTimeJwtTokenStorageCleanupService;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * This entity handles jwt usable only once. It's required as in some cases jwt must be active long enough
 * so that it can be used later on but this then unlocks possibility to spam the same link multiple times.
 *
 * With this entity tokens are stored until they expired
 *
 * @ORM\Entity(repositoryClass=OneTimeJwtTokenStorageRepository::class)
 */
class OneTimeJwtTokenStorage implements StorageInterface, EntityInterface
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

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $used = false;

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

    /**
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * @param bool $used
     */
    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }

}
