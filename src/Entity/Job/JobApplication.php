<?php

namespace App\Entity\Job;

use App\Entity\Email\Email;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Job\JobApplicationRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=JobApplicationRepository::class)
 */
class JobApplication implements EntityInterface
{
    use CreatedAndModifiedTrait;

    public const STATUS_EMAIL_PENDING = "PENDING";
    public const STATUS_EMAIL_SENT    = "SENT";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Ignore]
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $status = self::STATUS_EMAIL_PENDING;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="jobApplications", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Ignore]
    private User $user;

    /**
     * @ORM\OneToOne(targetEntity=Email::class, inversedBy="jobApplication", cascade={"persist", "remove"})
     */
    #[Ignore]
    private Email $email;

    /**
     * @ORM\ManyToOne(targetEntity=JobOfferInformation::class, inversedBy="jobApplications")
     * @ORM\JoinColumn(nullable=false)
     */
    private JobOfferInformation $jobOffer;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getJobOffer(): JobOfferInformation
    {
        return $this->jobOffer;
    }

    public function setJobOffer(JobOfferInformation $jobOffer): self
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }
}
