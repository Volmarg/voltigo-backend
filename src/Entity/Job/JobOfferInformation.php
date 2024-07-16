<?php

namespace App\Entity\Job;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Entity\Traits\SoftDeletableTrait;
use App\Repository\Job\JobOfferInformationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * Contains only base data about job offers for tracking and historical purposes
 *
 * @ORM\Entity(repositoryClass=JobOfferInformationRepository::class)
 */
class JobOfferInformation implements EntityInterface
{
    use CreatedAndModifiedTrait;
    use SoftDeletableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="text")
     */
    private string $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $companyName;

    /**
     * @ORM\Column(type="text")
     */
    private string $originalUrl;

    /**
     * @ORM\Column(type="integer")
     */
    private int $externalId;

    /**
     * @var ArrayCollection<int, JobApplication>
     * @ORM\OneToMany(targetEntity=JobApplication::class, mappedBy="jobOffer", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    #[Ignore]
    private $jobApplications;

    /**
     * @var ArrayCollection<int, JobSearchResult>
     * @ORM\ManyToMany(targetEntity=JobSearchResult::class, mappedBy="jobOfferInformations", fetch="EXTRA_LAZY")
     */
    #[Ignore]
    private $jobSearchResults;

    public function __construct()
    {
        $this->initCreatedAndModified();
        $this->jobApplications  = new ArrayCollection();
        $this->jobSearchResults = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getOriginalUrl(): ?string
    {
        return $this->originalUrl;
    }

    public function setOriginalUrl(string $originalUrl): self
    {
        $this->originalUrl = $originalUrl;

        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return Collection<int, JobApplication>
     */
    public function getJobApplications(): Collection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobApplication $jobApplication): self
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications[] = $jobApplication;
            $jobApplication->setJobOffer($this);
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): self
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getJobOffer() === $this) {
                $jobApplication->setJobOffer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSearchResult>
     */
    public function getJobSearchResults(): Collection
    {
        return $this->jobSearchResults;
    }

    public function addJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if (!$this->jobSearchResults->contains($jobSearchResult)) {
            $this->jobSearchResults[] = $jobSearchResult;
            $jobSearchResult->addJobOfferInformation($this);
        }

        return $this;
    }

    public function removeJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if ($this->jobSearchResults->removeElement($jobSearchResult)) {
            $jobSearchResult->removeJobOfferInformation($this);
        }

        return $this;
    }

    /**
     * @param JobSearchResult $jobSearchResult
     *
     * @return bool
     */
    public function belongsToJobSearch(JobSearchResult $jobSearchResult): bool
    {
        return $this->jobSearchResults->contains($jobSearchResult);
    }
}
