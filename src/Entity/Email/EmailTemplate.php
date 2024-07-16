<?php

namespace App\Entity\Email;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Entity\Traits\SoftDeletableTrait;
use App\Repository\Email\EmailTemplateRepository;
use App\Traits\Validation\BelongsToUserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @ORM\Entity(repositoryClass=EmailTemplateRepository::class)
 */
class EmailTemplate implements EntityInterface
{
    const FIELD_NAME_EMAIL_TEMPLATE_NAME = "emailTemplateName";
    const FIELD_NAME_EMAIL_DELETED       = "deleted";

    use BelongsToUserTrait;
    use SoftDeletableTrait;
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * Null means that templates are clone-able
     *
     * @Ignore
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="emailTemplates")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?User $user;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    #[NotBlank]
    private string $body;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Encrypted
     */
    private ?string $subject = null;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    #[NotBlank]
    private string $emailTemplateName;

    /**
     * @ORM\Column(type="text")
     */
    #[NotBlank]
    private string $exampleHtml;

    /**
     * Stores the base64 content of rendered {@see EmailTemplate::$exampleHtml}
     *
     * @ORM\Column(type="text")
     *
     * @var string $exampleBase64
     */
    #[NotBlank]
    private string $exampleBase64;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @Ignore
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSubjectSet(): bool
    {
        return !empty($this->getSubject());
    }

    public function getEmailTemplateName(): ?string
    {
        return $this->emailTemplateName;
    }

    public function setEmailTemplateName(string $emailTemplateName): self
    {
        $this->emailTemplateName = $emailTemplateName;

        return $this;
    }

    /**
     * @return string
     */
    public function getExampleHtml(): string
    {
        return $this->exampleHtml;
    }

    /**
     * @param string $exampleHtml
     */
    public function setExampleHtml(string $exampleHtml): void
    {
        $this->exampleHtml = $exampleHtml;
    }

    /**
     * @return string
     */
    public function getExampleBase64(): string
    {
        return $this->exampleBase64;
    }

    /**
     * @param string $exampleBase64
     */
    public function setExampleBase64(string $exampleBase64): void
    {
        $this->exampleBase64 = $exampleBase64;
    }

}
