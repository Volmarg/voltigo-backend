<?php

namespace App\Entity\Job;

use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Traits\ObjectValuesValidation;
use App\Traits\Validation\BelongsToUserTrait;
use JobSearcherBridge\Enum\JobOfferExtraction\StatusEnum;
use App\Repository\Job\JobSearchResultRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=JobSearchResultRepository::class)
 */
class JobSearchResult
{
    use BelongsToUserTrait;
    use CreatedAndModifiedTrait;
    use ObjectValuesValidation;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="array")
     */
    #[NotBlank]
    private array $keywords = [];

    /**
     * @ORM\Column(type="array")
     */
    #[NotBlank]
    private array $targetAreas = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $locationName = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $offersLimit = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $maxDistance = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Ignore]
    private ?DateTime $finished = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="jobSearchResults")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Ignore]
    private User $user;

    /**
     * @var ArrayCollection<int, JobOfferInformation>
     * @ORM\ManyToMany(targetEntity=JobOfferInformation::class, inversedBy="jobSearchResults")
     */
    #[Ignore]
    private $jobOfferInformations;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $externalExtractionId = null;

    /**
     * How many days is this offer still valid, before getting removed,
     * This is not an Entity field on purpose.
     *
     * @var int $validDaysNumber
     */
    private int $validDaysNumber;

    /**
     * Is nullable because initially no percentage value is known
     *
     * @var float|null
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $percentageDone = null;

    /**
     * @ORM\OneToOne(targetEntity=UserPointHistory::class, cascade={"persist", "remove"})
     */
    private $returnedPointsHistory;

    /**
     * - Is nullable, because there are cases where the entity has to be persisted first in oder to obtain it's id,
     *   then this id is used to create user point history entry,
     * - once the point history is created it can get bound to the search entity
     *
     * Further going, setter/getter is not allowing/returning NULL on purpose because it's expected to always
     * have related entity. The initial null is just required for such cases as described here.
     *
     * @ORM\OneToOne(targetEntity=UserPointHistory::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $userPointHistory = null;

    /**
     * This is not DB related field, just used to transfer data around
     *
     * @var int|null
     */
    private ?int $offersFoundCount = null;

    public function __construct()
    {
        $this->status               = SearchResultStatusEnum::PENDING->name;
        $this->jobOfferInformations = new ArrayCollection();
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    /**
     * Will return keywords as string
     *
     * @return string
     */
    public function getKeywordsAsString(): string
    {
        return implode(",", $this->getKeywords() ?? []);
    }

    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getTargetAreas(): ?array
    {
        return $this->targetAreas;
    }

    public function setTargetAreas(array $targetAreas): self
    {
        $this->targetAreas = $targetAreas;

        return $this;
    }

    /**
     * Will return only first target area
     * Keep in mind: even if there are multiple areas allowed in code, only one at once can be sent to search service
     *
     * @return string|null
     */
    public function getFirstTargetArea(): ?string
    {
        $targetAreas = $this->getTargetAreas();
        if (empty($targetAreas)) {
            return null;
        }

        return $targetAreas[array_key_first($targetAreas)];
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
    public function isRunning(): bool
    {
        return (SearchResultStatusEnum::WIP->name === $this->getStatus());
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return (SearchResultStatusEnum::PENDING->name === $this->getStatus());
    }

    /**
     * Set the entity status based on the status in offer handler.
     * The offer handler statuses are represented via {@see StatusEnum}
     */
    #[Ignore]
    public function setStatusFromJobOfferHandler(StatusEnum $statusEnum): void {
        $this->status = $this->getStatusFromOfferHandlerState($statusEnum);
    }

    /**
     * @param StatusEnum $statusEnum
     *
     * @return string
     */
    public function getStatusFromOfferHandlerState(StatusEnum $statusEnum): string
    {
        return match($statusEnum->value){
            StatusEnum::STATUS_FAILED->value             => SearchResultStatusEnum::ERROR->name,
            StatusEnum::STATUS_IMPORTED->value           => SearchResultStatusEnum::DONE->name,
            StatusEnum::STATUS_PARTIALLY_IMPORTED->value => SearchResultStatusEnum::PARTIALY_DONE->name,
            default                                      => SearchResultStatusEnum::PENDING->name,
        };
    }

    /**
     * @return DateTime|null
     */
    public function getFinished(): ?DateTime
    {
        return $this->finished;
    }

    /**
     * @param DateTime|null $finished
     */
    public function setFinished(?DateTime $finished): void
    {
        $this->finished = $finished;
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

    /**
     * @return ArrayCollection<int, JobOfferInformation>
     */
    public function getJobOfferInformations(): ArrayCollection
    {
        return $this->jobOfferInformations;
    }

    public function addJobOfferInformation(JobOfferInformation $jobOfferInformation): self
    {
        if (!$this->jobOfferInformations->contains($jobOfferInformation)) {
            $this->jobOfferInformations[] = $jobOfferInformation;
        }

        return $this;
    }

    public function removeJobOfferInformation(JobOfferInformation $jobOfferInformation): self
    {
        $this->jobOfferInformations->removeElement($jobOfferInformation);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * @param string|null $locationName
     */
    public function setLocationName(?string $locationName): void
    {
        $this->locationName = $locationName;
    }

    /**
     * @return int|null
     */
    public function getMaxDistance(): ?int
    {
        return $this->maxDistance;
    }

    /**
     * @param int|null $maxDistance
     */
    public function setMaxDistance(?int $maxDistance): void
    {
        $this->maxDistance = $maxDistance;
    }

    /**
     * @return int|null
     */
    public function getExternalExtractionId(): ?int
    {
        return $this->externalExtractionId;
    }

    /**
     * @param int|null $externalExtractionId
     */
    public function setExternalExtractionId(?int $externalExtractionId): void
    {
        $this->externalExtractionId = $externalExtractionId;
    }

    /**
     * @return int
     */
    public function getValidDaysNumber(): int
    {
        return $this->validDaysNumber;
    }

    /**
     * @param int $validDaysNumber
     */
    public function setValidDaysNumber(int $validDaysNumber): void
    {
        $this->validDaysNumber = $validDaysNumber;
    }

    /**
     * @return float|null
     */
    public function getPercentageDone(): ?float
    {
        return $this->percentageDone;
    }

    /**
     * @param float|null $percentageDone
     */
    public function setPercentageDone(?float $percentageDone): void
    {
        $this->percentageDone = $percentageDone;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function validateValues(ExecutionContextInterface $context): void
    {
        /** @var JobSearchResult $object*/
        $object = $context->getObject();

        if (
                !is_null($object->getMaxDistance())
            &&  $object->getMaxDistance() < 0
        ) {
            $context->buildViolation("Expecting the distance to be either 0 or positive value.")->atPath("maxDistance")->addViolation();
        }

        if (
                !is_null($object->getMaxDistance())
            &&  is_null($object->getLocationName())
        ) {
            $context->buildViolation("Distance is set yet location name is missing")->atPath("locationName")->addViolation();
        }
    }

    public function getReturnedPointsHistory(): ?UserPointHistory
    {
        return $this->returnedPointsHistory;
    }

    public function setReturnedPointsHistory(?UserPointHistory $returnedPointsHistory): self
    {
        $this->returnedPointsHistory = $returnedPointsHistory;

        return $this;
    }

    /**
     * @return UserPointHistory
     */
    public function getUserPointHistory(): UserPointHistory
    {
        return $this->userPointHistory;
    }

    /**
     * @param UserPointHistory $userPointHistory
     */
    public function setUserPointHistory(UserPointHistory $userPointHistory): void
    {
        $this->userPointHistory = $userPointHistory;
    }

    public function getOffersFoundCount(): ?int
    {
        return $this->offersFoundCount;
    }

    public function setOffersFoundCount(?int $offersFoundCount): void
    {
        $this->offersFoundCount = $offersFoundCount;
    }

    public function getOffersLimit(): ?int
    {
        return $this->offersLimit;
    }

    public function setOffersLimit(?int $offersLimit): void
    {
        $this->offersLimit = $offersLimit;
    }

    /**
     * Check if search result is done
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return (
                SearchResultStatusEnum::DONE->name === $this->getStatus()
            ||  SearchResultStatusEnum::PARTIALY_DONE->name === $this->getStatus()
        );
    }
}
