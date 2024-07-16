<?php

namespace App\DTO\Frontend\JobOffer\Filter;

use App\Enum\Job\Filter\KeywordsFilteringRule;
use App\Enum\Job\Filter\PostedRule;

/**
 * Represents state of all the job offer filters present on frontend
 */
class FilterDTO
{
    private ?int $salaryMin;
    private ?int $salaryMax;
    private ?int $ageOld;
    private ?int $employeesMin;

    public function __construct(
        private ?KeywordsFilteringRule $mandatoryIncludedKeywordsFilteringRule = null,
        private ?KeywordsFilteringRule $mandatoryExcludedKeywordsFilteringRule = null,
        private ?PostedRule            $whenWasItPosted = null,
        private bool    $speakLanguageRequired = false,
        private bool    $locationRequired = false,
        private bool    $salaryRequired = false,
        private bool    $emailRequired = false,
        private bool    $phoneRequired = false,
        private bool    $mustBeRemote = false,
        private bool    $includeWithoutPostedDate = true,
        private bool    $areAllVisibleOffersSelected = false,
        int|string|null $salaryMin = null,                  # mixed type due to serializer crashing as null from front = empty string from symfony
        int|string|null $salaryMax = null,                  # mixed type due to serializer crashing as null from front = empty string from symfony
        private array   $mandatoryExcludedKeywords = [],
        private array   $mandatoryIncludedKeywords = [],
        private array   $cities = [],
        private array   $countries = [],
        private array   $languages = [],
        int|string|null $ageOld = null,                     # mixed type due to serializer crashing as null from front = empty string from symfony
        int|string|null $employeesMin = null,               # mixed type due to serializer crashing as null from front = empty string from symfony
        private bool    $onlyNewOffers = true,
        private array   $excludedCompanyNames = [],
        private array   $includedCompanyNames = [],
        private bool    $countryNameRequired = false,
        private bool    $employeeCountRequired = false,
        private bool    $ageRequired = false,
    ) {
        $this->salaryMin    = ("" === $salaryMin ? null : $salaryMin);
        $this->salaryMax    = ("" === $salaryMax ? null : $salaryMax);
        $this->ageOld       = ("" === $ageOld ? null : $ageOld);
        $this->employeesMin = ("" === $employeesMin ? null : $employeesMin);
    }

    /**
     * @return KeywordsFilteringRule|null
     */
    public function getMandatoryIncludedKeywordsFilteringRule(): ?KeywordsFilteringRule
    {
        return $this->mandatoryIncludedKeywordsFilteringRule;
    }

    /**
     * @param KeywordsFilteringRule|null $mandatoryIncludedKeywordsFilteringRule
     */
    public function setMandatoryIncludedKeywordsFilteringRule(?KeywordsFilteringRule $mandatoryIncludedKeywordsFilteringRule): void {
        $this->mandatoryIncludedKeywordsFilteringRule = $mandatoryIncludedKeywordsFilteringRule;
    }

    /**
     * @return KeywordsFilteringRule|null
     */
    public function getMandatoryExcludedKeywordsFilteringRule(): ?KeywordsFilteringRule
    {
        return $this->mandatoryExcludedKeywordsFilteringRule;
    }

    /**
     * @param KeywordsFilteringRule|null $mandatoryExcludedKeywordsFilteringRule
     */
    public function setMandatoryExcludedKeywordsFilteringRule(?KeywordsFilteringRule $mandatoryExcludedKeywordsFilteringRule): void {
        $this->mandatoryExcludedKeywordsFilteringRule = $mandatoryExcludedKeywordsFilteringRule;
    }

    /**
     * @return PostedRule|null
     */
    public function getWhenWasItPosted(): ?PostedRule
    {
        return $this->whenWasItPosted;
    }

    /**
     * @param PostedRule|null $whenWasItPosted
     */
    public function setWhenWasItPosted(?PostedRule $whenWasItPosted): void
    {
        $this->whenWasItPosted = $whenWasItPosted;
    }

    /**
     * @return bool
     */
    public function isSpeakLanguageRequired(): bool
    {
        return $this->speakLanguageRequired;
    }

    /**
     * @param bool $speakLanguageRequired
     */
    public function setSpeakLanguageRequired(bool $speakLanguageRequired): void
    {
        $this->speakLanguageRequired = $speakLanguageRequired;
    }

    /**
     * @return bool
     */
    public function isLocationRequired(): bool
    {
        return $this->locationRequired;
    }

    /**
     * @param bool $locationRequired
     */
    public function setLocationRequired(bool $locationRequired): void
    {
        $this->locationRequired = $locationRequired;
    }

    /**
     * @return bool
     */
    public function isSalaryRequired(): bool
    {
        return $this->salaryRequired;
    }

    /**
     * @param bool $salaryRequired
     */
    public function setSalaryRequired(bool $salaryRequired): void
    {
        $this->salaryRequired = $salaryRequired;
    }

    /**
     * @return bool
     */
    public function isEmailRequired(): bool
    {
        return $this->emailRequired;
    }

    /**
     * @param bool $emailRequired
     */
    public function setEmailRequired(bool $emailRequired): void
    {
        $this->emailRequired = $emailRequired;
    }

    /**
     * @return bool
     */
    public function isPhoneRequired(): bool
    {
        return $this->phoneRequired;
    }

    /**
     * @param bool $phoneRequired
     */
    public function setPhoneRequired(bool $phoneRequired): void
    {
        $this->phoneRequired = $phoneRequired;
    }

    /**
     * @return bool
     */
    public function isMustBeRemote(): bool
    {
        return $this->mustBeRemote;
    }

    /**
     * @param bool $mustBeRemote
     */
    public function setMustBeRemote(bool $mustBeRemote): void
    {
        $this->mustBeRemote = $mustBeRemote;
    }

    /**
     * @return bool
     */
    public function isIncludeWithoutPostedDate(): bool
    {
        return $this->includeWithoutPostedDate;
    }

    /**
     * @param bool $includeWithoutPostedDate
     */
    public function setIncludeWithoutPostedDate(bool $includeWithoutPostedDate): void
    {
        $this->includeWithoutPostedDate = $includeWithoutPostedDate;
    }

    /**
     * @return bool
     */
    public function isAreAllVisibleOffersSelected(): bool
    {
        return $this->areAllVisibleOffersSelected;
    }

    /**
     * @param bool $areAllVisibleOffersSelected
     */
    public function setAreAllVisibleOffersSelected(bool $areAllVisibleOffersSelected): void
    {
        $this->areAllVisibleOffersSelected = $areAllVisibleOffersSelected;
    }

    /**
     * @return int|null
     */
    public function getSalaryMin(): ?int
    {
        return $this->salaryMin;
    }

    /**
     * @param int|null $salaryMin
     */
    public function setSalaryMin(?int $salaryMin): void
    {
        $this->salaryMin = $salaryMin;
    }

    /**
     * @return int|null
     */
    public function getSalaryMax(): ?int
    {
        return $this->salaryMax;
    }

    /**
     * @param int|null $salaryMax
     */
    public function setSalaryMax(?int $salaryMax): void
    {
        $this->salaryMax = $salaryMax;
    }

    /**
     * @return array
     */
    public function getMandatoryExcludedKeywords(): array
    {
        return $this->mandatoryExcludedKeywords;
    }

    /**
     * @param array $mandatoryExcludedKeywords
     */
    public function setMandatoryExcludedKeywords(array $mandatoryExcludedKeywords): void
    {
        $this->mandatoryExcludedKeywords = $mandatoryExcludedKeywords;
    }

    /**
     * @return array
     */
    public function getMandatoryIncludedKeywords(): array
    {
        return $this->mandatoryIncludedKeywords;
    }

    /**
     * @param array $mandatoryIncludedKeywords
     */
    public function setMandatoryIncludedKeywords(array $mandatoryIncludedKeywords): void
    {
        $this->mandatoryIncludedKeywords = $mandatoryIncludedKeywords;
    }

    /**
     * @return array
     */
    public function getCities(): array
    {
        return $this->cities;
    }

    /**
     * @param array $cities
     */
    public function setCities(array $cities): void
    {
        $this->cities = $cities;
    }

    /**
     * @return array
     */
    public function getCountries(): array
    {
        return $this->countries;
    }

    /**
     * @param array $countries
     */
    public function setCountries(array $countries): void
    {
        $this->countries = $countries;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param array $languages
     */
    public function setLanguages(array $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return int|null
     */
    public function getAgeOld(): ?int
    {
        return $this->ageOld;
    }

    /**
     * @return int|null
     */
    public function getEmployeesMin(): ?int
    {
        return $this->employeesMin;
    }

    /**
     * @param int|null $ageOld
     */
    public function setAgeOld(?int $ageOld): void
    {
        $this->ageOld = $ageOld;
    }

    /**
     * @param int|null $employeesMin
     */
    public function setEmployeesMin(?int $employeesMin): void
    {
        $this->employeesMin = $employeesMin;
    }

    /**
     * @return bool
     */
    public function isOnlyNewOffers(): bool
    {
        return $this->onlyNewOffers;
    }

    /**
     * @param bool $onlyNewOffers
     */
    public function setOnlyNewOffers(bool $onlyNewOffers): void
    {
        $this->onlyNewOffers = $onlyNewOffers;
    }

    /**
     * @return array
     */
    public function getExcludedCompanyNames(): array
    {
        return $this->excludedCompanyNames;
    }

    /**
     * @param array $excludedCompanyNames
     */
    public function setExcludedCompanyNames(array $excludedCompanyNames): void
    {
        $this->excludedCompanyNames = $excludedCompanyNames;
    }

    /**
     * @return array
     */
    public function getIncludedCompanyNames(): array
    {
        return $this->includedCompanyNames;
    }

    /**
     * @param array $includedCompanyNames
     */
    public function setIncludedCompanyNames(array $includedCompanyNames): void
    {
        $this->includedCompanyNames = $includedCompanyNames;
    }

    /**
     * @return bool
     */
    public function isCountryNameRequired(): bool
    {
        return $this->countryNameRequired;
    }

    /**
     * @param bool $countryNameRequired
     */
    public function setCountryNameRequired(bool $countryNameRequired): void
    {
        $this->countryNameRequired = $countryNameRequired;
    }

    /**
     * @return bool
     */
    public function isEmployeeCountRequired(): bool
    {
        return $this->employeeCountRequired;
    }

    /**
     * @param bool $employeeCountRequired
     */
    public function setEmployeeCountRequired(bool $employeeCountRequired): void
    {
        $this->employeeCountRequired = $employeeCountRequired;
    }

    /**
     * @return bool
     */
    public function isAgeRequired(): bool
    {
        return $this->ageRequired;
    }

    /**
     * @param bool $ageRequired
     */
    public function setAgeRequired(bool $ageRequired): void
    {
        $this->ageRequired = $ageRequired;
    }

    /**
     * Some extra fixes because for example vue sends empty string when value is 0 or is empty false,
     * Resolving this in case of bool is working fine, but symfony can't resolve empty string to int
     */
    public function selfCorrect(): void
    {
        $this->setSalaryMin($this->getSalaryMin() ?: 0);
        $this->setSalaryMax($this->getSalaryMax() ?: 0);
    }

}