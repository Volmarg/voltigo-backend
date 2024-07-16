<?php

namespace App\Entity\Email;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Controller\Core\Env;
use App\Entity\Job\JobApplication;
use App\Entity\Security\User;
use App\Entity\Traits\AnonymizableTrait;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Enum\Email\TemplateIdentifierEnum;
use App\Repository\Email\EmailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailRepository::class)
 */
class Email
{
    /**
     * Some data remains as reference for any further debugging and connections to external tools like:
     * - externalId (id of E-Mail in external tool),
     */
    use AnonymizableTrait;
    use CreatedAndModifiedTrait;

    /**
     * E-Mail is waiting to be sent
     */
    public const KEY_STATUS_PENDING = "PENDING";

    /**
     * E-Mail has been sent by external tool
     */
    public const KEY_STATUS_SENT_BY_EXTERNAL_TOOL = "SENT_BY_EXTERNAL_TOOL";

    /**
     * Something went wrong while trying to transfer an E-Mail
     */
    public const KEY_STATUS_ERROR = "ERROR";

    /**
     * E-Mail has been transferred to sending tool
     */
    public const KEY_STATUS_TRANSFERRED_TO_EXTERNAL_TOOL = "TRANSFERRED_TO_EXTERNAL_TOOL";

    /**
     * One of the recipients is invalid
     */
    public const KEY_INVALID_RECIPIENT = "INVALID_RECIPIENT";

    /**
     * One of the E-Mail recipients has been blacklisted, might be that user applied on offer but on the very moment
     * the company sent blacklisting request (shortly before cron started) thus it cannot be sent
     */
    public const KEY_BLOCKED_BLACKLISTED_RECIPIENT = "BLOCKED_BLACKLISTED_RECIPIENT";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $body;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $subject;

    /**
     * @ORM\ManyToOne(targetEntity=EmailTemplate::class)
     */
    private ?EmailTemplate $template;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255)
     */
    private ?string $identifier = null;

    /**
     * If it's null then it's sent from/by system.
     * It's more about "who triggered this E-Mail" / "who initialised it".
     * In cases where user cannot be identified as "triggering" person, then such E-Mail is marked as "system", example:
     * - password reset (user is not logged in, so it's system based)
     *
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="generatedEmails")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?User $sender = null;

    /**
     * @var Array<string>
     * @ORM\Column(type="array")
     */
    private array $recipients = [];

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $sendCopyToSender = false;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $toolName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $externalId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $error;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $status = self::KEY_STATUS_PENDING;

    /**
     * @ORM\OneToOne(targetEntity=JobApplication::class, mappedBy="email", cascade={"persist", "remove"})
     */
    private JobApplication $jobApplication;

    /**
     * @ORM\OneToMany(targetEntity=EmailAttachment::class, mappedBy="email", orphanRemoval=true, cascade={"PERSIST", "REMOVE"})
     */
    private $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Check if E-Mail is based on the email-builder
     *
     * @return bool
     */
    public function isBuilderTemplateBased(): bool
    {
        return !empty($this->getTemplate());
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * Check if sender is set or not
     * @return bool
     */
    public function isSenderSet(): bool
    {
        return null !== $this->getSender();
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipients(): ?array
    {
        return $this->recipients;
    }

    /**
     * @return int
     */
    public function countRecipients(): int
    {
        return count($this->getRecipients());
    }

    public function setRecipients(array $recipients): self
    {
        if (Env::isDev()) {
            $this->recipients = [Env::getAdminEmail()];
            return $this;
        }

        $this->recipients = $recipients;

        return $this;
    }

    public function addRecipient(string $recipient): void
    {
        if (Env::isDev()) {
            $this->recipients = [Env::getAdminEmail()];
            return;
        }

        $this->recipients[] = $recipient;
    }

    public function isSendCopyToSender(): bool
    {
        return $this->sendCopyToSender;
    }

    public function setSendCopyToSender(bool $sendCopyToSender): self
    {
        $this->sendCopyToSender = $sendCopyToSender;

        return $this;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function setToolName(?string $toolName): self
    {
        $this->toolName = $toolName;

        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getJobApplication(): ?JobApplication
    {
        return $this->jobApplication;
    }

    /**
     * @return bool
     */
    public function hasJobApplication(): bool
    {
        return isset($this->jobApplication);
    }

    public function setJobApplication(?JobApplication $jobApplication): self
    {
        // unset the owning side of the relation if necessary
        if ($jobApplication === null && $this->jobApplication !== null) {
            $this->jobApplication->setEmail(null);
        }

        // set the owning side of the relation if necessary
        if ($jobApplication !== null && $jobApplication->getEmail() !== $this) {
            $jobApplication->setEmail($this);
        }

        $this->jobApplication = $jobApplication;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isTransferred(): bool
    {
        return ($this->getStatus() === self::KEY_STATUS_TRANSFERRED_TO_EXTERNAL_TOOL);
    }

    /**
     * Will return first found E-Mail address from recipients list
     *
     * @return string|null
     */
    public function getFirstRecipient(): ?string
    {
        $recipients = $this->getRecipients();
        if (empty($recipients)) {
            return null;
        }

        return $recipients[array_key_first($recipients)];
    }

    /**
     * @return Collection|EmailAttachment[]
     */
    public function getAttachments(): Collection | array
    {
        return $this->attachments;
    }

    /**
     * @param Collection|EmailAttachment[] $attachments
     */
    public function setAttachments($attachments): void
    {
        $this->attachments = $attachments;
    }

    public function addAttachment(EmailAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments[] = $attachment;
            $attachment->setEmail($this);
        }

        return $this;
    }

    public function removeAttachment(EmailAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getEmail() === $this) {
                $attachment->setEmail(null);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return ($this->getStatus() === self::KEY_STATUS_SENT_BY_EXTERNAL_TOOL);
    }

    /**
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string|null $identifier
     */
    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return bool
     */
    public function isTemplateTestEmail(): bool
    {
        return ($this->identifier === TemplateIdentifierEnum::TEMPLATE_TEST_EMAIL->name);
    }

}
