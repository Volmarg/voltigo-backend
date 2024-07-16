<?php

namespace App\DTO\Internal\Job;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents data used for job offers search/fetching from external sources
 */
class JobSearchDTO
{
    #[NotBlank]
    private array $jobSearchKeywords = [];

    #[NotBlank]
    private string $targetArea = "";

    private ?string $locationName = null;

    private ?int $maxDistance = null;

    private ?int $offersLimit = null;

    /**
     * @return array
     */
    public function getJobSearchKeywords(): array
    {
        return $this->jobSearchKeywords;
    }

    /**
     * @param array $jobSearchKeywords
     */
    public function setJobSearchKeywords(array $jobSearchKeywords): void
    {
        $this->jobSearchKeywords = $jobSearchKeywords;
    }

    /**
     * @return int
     */
    public function countJobSearchKeywords(): int
    {
        return count($this->getJobSearchKeywords());
    }

    /**
     * @return string
     */
    public function getTargetArea(): string
    {
        return $this->targetArea;
    }

    /**
     * @param string $targetArea
     */
    public function setTargetArea(string $targetArea): void
    {
        $this->targetArea = $targetArea;
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

    public function getOffersLimit(): ?int
    {
        return $this->offersLimit;
    }

    public function setOffersLimit(?int $offersLimit): void
    {
        $this->offersLimit = $offersLimit;
    }

}