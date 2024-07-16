<?php

namespace App\Entity\Storage\Ban;

use App\Entity\Storage\StorageInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base storage for any banning related logic
 */
abstract class BaseBanStorage implements StorageInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * Can be literally any const, string etc. something that will allow to track down
     * where the ban was set, preferably setting `class::function`.
     *
     * This could also be manual entry in DB, so the scheme `class::function` is not getting enforced
     *
     * @var string
     * @ORM\Column(type="text")
     */
    protected string $issuedBy;

    /**
     * @var bool $lifetime
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $lifetime = false;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected string $reason;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $validTill;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isLifetime(): bool
    {
        return $this->lifetime;
    }

    /**
     * @param bool $lifetime
     */
    public function setLifetime(bool $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * @return void
     */
    public function makeLifetime(): void
    {
        $this->lifetime = true;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return DateTime|null
     */
    public function getValidTill(): ?DateTime
    {
        return $this->validTill;
    }

    /**
     * @param DateTime|null $validTill
     */
    public function setValidTill(?DateTime $validTill): void
    {
        $this->validTill = $validTill;
    }

    /**
     * @return string
     */
    public function getIssuedBy(): string
    {
        return $this->issuedBy;
    }

    /**
     * @param string $issuedBy
     */
    public function setIssuedBy(string $issuedBy): void
    {
        $this->issuedBy = $issuedBy;
    }

}
