<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Storage\AmqpStorageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AmqpStorageRepository::class)
 */
class AmqpStorage implements StorageInterface, EntityInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=true, length=300)
     */
    private string $targetClass;

    /**
     * @ORM\Column(type="string", length=900)
     */
    private string $message;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $uniqueId;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="amqpStorageEntries")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=AmqpStorage::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $relatedStorageEntry;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $expectResponse = false;

    public function __construct()
    {
        $this->uniqueId = uniqid();
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
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @param string $targetClass
     */
    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     */
    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRelatedStorageEntry(): ?self
    {
        return $this->relatedStorageEntry;
    }

    public function setRelatedStorageEntry(?self $relatedStorageEntry): self
    {
        $this->relatedStorageEntry = $relatedStorageEntry;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpectResponse(): bool
    {
        return $this->expectResponse;
    }

    /**
     * @param bool $expectResponse
     */
    public function setExpectResponse(bool $expectResponse): void
    {
        $this->expectResponse = $expectResponse;
    }

    /**
     * @param AmqpStorage[]    $entries
     * @param AmqpStorage|null $storageEntry
     *
     * @return array
     */
    public function getAllNestedRelatedStorageEntries(array $entries = [], ?AmqpStorage $storageEntry = null): array
    {
        $usedEntry = $storageEntry ?? $this->getRelatedStorageEntry();
        if(empty($usedEntry)){
            return $entries;
        }

        $entries[] = $usedEntry;
        if (empty($usedEntry->getRelatedStorageEntry())) {
            return $entries;
        }

        $entries = [
            ...$entries,
            ...$usedEntry->getAllNestedRelatedStorageEntries($entries, $usedEntry)
        ];

        return $entries;
    }
}
