<?php

namespace App\Entity\Email;

use App\Entity\Traits\CreatedTrait;
use App\Repository\Email\EmailAttachmentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailAttachmentRepository::class)
 * @ORM\Table(name="email_attachment")
 */
class EmailAttachment
{
    use CreatedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Email::class, inversedBy="Modules")
     * @ORM\JoinColumn(nullable=false)
     */
    private Email $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $path;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $fileName;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $removeFile = true;

    /**
     * @param string $path
     * @param string $fileName
     */
    public function __construct(string $path, string $fileName)
    {
        $this->initCreated();
        $this->path     = $path;
        $this->fileName = $fileName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): self
    {
        $this->email = $email;

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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRemoveFile(): bool
    {
        return $this->removeFile;
    }

    /**
     * @param bool $removeFile
     */
    public function setRemoveFile(bool $removeFile): void
    {
        $this->removeFile = $removeFile;
    }

}
