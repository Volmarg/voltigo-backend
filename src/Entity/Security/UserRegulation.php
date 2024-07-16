<?php

namespace App\Entity\Security;

use App\Entity\Regulation\RegulationData;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Security\UserRegulationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRegulationRepository::class)
 * @ORM\Table(
 *      name="user_regulation",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "identifier", "created", "regulation_data_id"})
 *      }
 * )
 */
class UserRegulation
{
    use CreatedAndModifiedTrait;

    public const REGULATION_EMAIL_BUILDER_GENERAL_USAGE = "EMAIL_BUILDER_GENERAL_USAGE";
    public const REGULATIONS_PLATFORM_TERMS_OF_USAGE    = "PLATFORM_TERMS_OF_USAGE";

    public const ALLOWED_REGULATIONS = [
        self::REGULATION_EMAIL_BUILDER_GENERAL_USAGE,
        self::REGULATIONS_PLATFORM_TERMS_OF_USAGE,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $identifier;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $accepted = false;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="regulations")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity=RegulationData::class, inversedBy="userRegulations", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="regulation_data_id")
     */
    private ?RegulationData $data = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $acceptDate = null;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function isAccepted(): ?bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;

        return $this;
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

    /**
     * Check if given regulation is allowed or not
     *
     * @param string $identifier
     *
     * @return bool
     */
    public static function isRegulationAllowed(string $identifier): bool
    {
        return in_array($identifier, self::ALLOWED_REGULATIONS);
    }

    public function getData(): ?RegulationData
    {
        return $this->data;
    }

    public function setData(?RegulationData $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAcceptDate(): ?DateTime
    {
        return $this->acceptDate;
    }

    /**
     * @param DateTime|null $acceptDate
     */
    public function setAcceptDate(?DateTime $acceptDate): void
    {
        $this->acceptDate = $acceptDate;
    }

}
