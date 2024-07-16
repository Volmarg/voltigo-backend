<?php

namespace App\Entity\File;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Email\EmailTemplate;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedTrait;
use App\Enum\File\UploadedFileSourceEnum;
use App\Repository\File\UploadedFileRepository;
use App\Service\File\Path\PathService;
use App\Traits\Validation\BelongsToUserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UploadedFileRepository::class)
 */
class UploadedFile implements EntityInterface
{
    use BelongsToUserTrait;
    use CreatedTrait;

    public const SERIALIZATION_GROUP_BASE_DATA = "baseData";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(self::SERIALIZATION_GROUP_BASE_DATA)]
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(self::SERIALIZATION_GROUP_BASE_DATA)]
    private ?string $userBasedName = null;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    #[Groups(self::SERIALIZATION_GROUP_BASE_DATA)]
    private string $localFileName;

    /**
     * @ORM\Column(type="string", length=255)
e     */
    #[Groups(self::SERIALIZATION_GROUP_BASE_DATA)]
    private string $originalName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(self::SERIALIZATION_GROUP_BASE_DATA)]
    private ?string $publicPath = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $path;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="uploadedFiles")
     */
    private User $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $mimeType;

    /**
     * @ORM\Column(type="decimal", precision=50, scale=2)
     */
    private float $sizeMb;

    /**
     * @var UploadedFileSourceEnum
     * {@link https://www.doctrine-project.org/2022/01/11/orm-2.11.html}
     *
     * @ORM\Column(type="string", enumType="App\Enum\File\UploadedFileSourceEnum")
     */
    private UploadedFileSourceEnum $source;

    /**
     * That flag is important because any user file could've been used to build the {@see EmailTemplate} and
     * if user uploaded something on the server and have sent the E-Mail then it will contain the links to the
     * files on server, thus these must be ALWAYS reachable.
     *
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $deletable = true;

    public function __construct()
    {
        $this->initCreated();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocalFileName(): ?string
    {
        return $this->localFileName;
    }

    public function setLocalFileName(string $localFileName): self
    {
        $this->localFileName = $localFileName;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSizeMb(): ?string
    {
        return $this->sizeMb;
    }

    public function setSizeMb(string $sizeMb): self
    {
        $this->sizeMb = $sizeMb;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathWithFileName(): string
    {
        $usedPath = PathService::setTrailingSlash($this->getPath());

        return $usedPath . $this->getLocalFileName();
    }

    /**
     * @return string
     */
    public function getLinkedPathWithFileName(): string
    {
        $usedPath = PathService::setTrailingSlash($this->getPublicPath());

        return $usedPath . $this->getLocalFileName();
    }

    /**
     * @return string|null
     */
    public function getUserBasedName(): ?string
    {
        return $this->userBasedName;
    }

    /**
     * @param string|null $userBasedName
     */
    public function setUserBasedName(?string $userBasedName): void
    {
        $this->userBasedName = $userBasedName;
    }

    /**
     * @return UploadedFileSourceEnum
     */
    public function getSource(): UploadedFileSourceEnum
    {
        return $this->source;
    }

    /**
     * @param UploadedFileSourceEnum $source
     */
    public function setSource(UploadedFileSourceEnum $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string|null
     */
    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    /**
     * @param string|null $publicPath
     */
    public function setPublicPath(?string $publicPath): void
    {
        $this->publicPath = $publicPath;
    }

    /**
     * Will attempt to remove the file from server
     */
    public function removeFromServer(): void
    {
        @$isRemoved = unlink($this->getPathWithFileName());
        if (!$isRemoved) {
            $possibleError = json_encode(error_get_last());
            throw new FileException("Could not remove the file: {$this->getPathWithFileName()}. The possible error: " . $possibleError);
        }
    }

    /**
     * Check if file exists on server
     * @return bool
     */
    public function isOnServer(): bool
    {
        return file_exists($this->getPathWithFileName());
    }

    /**
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     */
    public function setDeletable(bool $deletable): void
    {
        $this->deletable = $deletable;
    }

}
