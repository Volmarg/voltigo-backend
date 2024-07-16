<?php

namespace App\Service\Api\JobSearcher\Filter;

use App\DTO\Frontend\JobOffer\Filter\FilterDTO;
use App\Entity\Job\JobSearchResult;
use App\Enum\Job\Filter\KeywordsFilteringRule;
use App\Enum\Job\Filter\PostedRule;
use DateTime;
use Exception;
use JobSearcherBridge\DTO\Offers\Filter\JobOfferFilterDto;
use JobSearcherBridge\DTO\Offers\Filter\SubFilters\JobPostedDateTimeFilterDto;

/**
 * Handles the:
 * - {@see JobOfferFilterDto},
 * - {@see FilterDTO}
 */
class FilterService
{
    /**
     * Will build {@see JobOfferFilterDto} from {@see FilterDTO} (front filter for users)
     *
     * @param FilterDTO $filterDTO
     *
     * @return JobOfferFilterDto
     * @throws Exception
     */
    public function buildSearcherFilterFromFrontFilter(FilterDTO $filterDTO): JobOfferFilterDto
    {
        $jobOfferFilterDto = new JobOfferFilterDto();

        $jobOfferFilterDto->setIncludeJobOfferWithoutPostedDateTime($filterDTO->isIncludeWithoutPostedDate());
        $jobOfferFilterDto->setMustBeRemote($filterDTO->isMustBeRemote());
        $jobOfferFilterDto->setMustHaveMail($filterDTO->isEmailRequired());
        $jobOfferFilterDto->setMustHavePhone($filterDTO->isPhoneRequired());
        $jobOfferFilterDto->setMustHaveSalary($filterDTO->isSalaryRequired());
        $jobOfferFilterDto->setMustHaveLocation($filterDTO->isLocationRequired());
        $jobOfferFilterDto->setOnlyNewOffers($filterDTO->isOnlyNewOffers());
        $jobOfferFilterDto->setAgeRequired($filterDTO->isAgeRequired());
        $jobOfferFilterDto->setEmployeeCountRequired($filterDTO->isEmployeeCountRequired());
        $jobOfferFilterDto->setCountryNameRequired($filterDTO->isCountryNameRequired());
        $jobOfferFilterDto->setIncludeJobOffersWithoutHumanLanguagesMentioned(!$filterDTO->isSpeakLanguageRequired());

        $jobOfferFilterDto->setMinSalary($filterDTO->getSalaryMin());
        $jobOfferFilterDto->setMaxSalary($filterDTO->getSalaryMax());

        $jobOfferFilterDto->setExcludedKeywords($filterDTO->getMandatoryExcludedKeywords());
        $jobOfferFilterDto->setMandatoryIncludedKeywords($filterDTO->getMandatoryIncludedKeywords());

        $jobOfferFilterDto->setIncludedCompanyNames($filterDTO->getIncludedCompanyNames());
        $jobOfferFilterDto->setExcludedCompanyNames($filterDTO->getExcludedCompanyNames());

        $jobOfferFilterDto->setMandatoryHumanLanguages($filterDTO->getLanguages());
        $jobOfferFilterDto->setCountryNames($filterDTO->getCountries());
        $jobOfferFilterDto->setLocationNames($filterDTO->getCities());
        $jobOfferFilterDto->setCompanyMinYearsOld($filterDTO->getAgeOld());
        $jobOfferFilterDto->setCompanyEmployeesMinCount($filterDTO->getEmployeesMin());

        $this->setJobOfferFilterKeywordsModeFromFrontFilter($jobOfferFilterDto, $filterDTO);
        $this->setJobOfferDateFilterRuleFromFrontFilter($jobOfferFilterDto, $filterDTO);

        return $jobOfferFilterDto;
    }

    /**
     * Will build {@see FilterDTO} (front filter for users) from {@see JobOfferFilterDto} (searcher filter)
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     *
     * @return FilterDTO
     */
    public function buildFrontFilterFromSearcherFilter(JobOfferFilterDto $jobOfferFilterDto): FilterDTO
    {
        $filterDTO = new FilterDTO();

        $filterDTO->setOnlyNewOffers($jobOfferFilterDto->isOnlyNewOffers());
        $filterDTO->setMustBeRemote($jobOfferFilterDto->isMustBeRemote());
        $filterDTO->setSalaryRequired($jobOfferFilterDto->isMustHaveSalary());
        $filterDTO->setEmailRequired($jobOfferFilterDto->isMustHaveMail());
        $filterDTO->setPhoneRequired($jobOfferFilterDto->isMustHavePhone());
        $filterDTO->setLocationRequired($jobOfferFilterDto->isMustHaveLocation());
        $filterDTO->setIncludeWithoutPostedDate($jobOfferFilterDto->isIncludeJobOfferWithoutPostedDateTime());
        $filterDTO->setSpeakLanguageRequired(!$jobOfferFilterDto->isIncludeJobOffersWithoutHumanLanguagesMentioned());
        $filterDTO->setLocationRequired($jobOfferFilterDto->isMustHaveLocation());

        $filterDTO->setMandatoryIncludedKeywords($jobOfferFilterDto->getMandatoryIncludedKeywords() ?? []);

        $this->setFrontFilterKeywordsModeFromJobOfferFilter($jobOfferFilterDto, $filterDTO);

        return $filterDTO;
    }

    /**
     * Default filter used for fetching offers initially upon opening the result page
     *
     * [> VERY IMPORTANT <]
     * All the filters must be AS MUCH TOLERANT AS POSSIBLE,
     * meaning that NOTHING should be restricted by them, reason is that it initially must get all the offers for front
     * as front is building filter options (like for example human languages) based on the offers that were initially fetched
     * and this filter WILL be used for initial fetch
     *
     * @return JobOfferFilterDto
     */
    public function buildDefaultJobOfferSearchFilter(): JobOfferFilterDto
    {
        $filter = new JobOfferFilterDto();

        $filter->setIncludePreviouslyFoundOffers(false);
        $filter->setOnlyNewOffers(true);
        $filter->setIncludeJobOfferDetailWithoutLanguageDetected(true);
        $filter->setIncludeJobOffersWithoutHumanLanguagesMentioned(true);
        $filter->setIncludeJobOfferWithoutPostedDateTime(true);
        $filter->setCountryNameRequired(false);
        $filter->setAgeRequired(false);
        $filter->setEmployeeCountRequired(false);
        $filter->setIncludeOffersWithoutEmployeesCount(true);
        $filter->setMustBeRemote(false);
        $filter->setMustHaveSalary(false);
        $filter->setMustHavePhone(false);
        $filter->setMustHaveMail(true);
        $filter->setMustHaveLocation(false);

        return $filter;
    }

    /**
     * Will create the filters used in offer-searcher based on the offers search criteria
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return JobOfferFilterDto
     */
    public function buildSearchFilterFromJobSearch(JobSearchResult $jobSearchResult): JobOfferFilterDto
    {
        $searchFilter = $this->buildDefaultJobOfferSearchFilter();

        $searchFilter->setMandatoryIncludedKeywords($jobSearchResult->getKeywords() ?? []);
        $searchFilter->setMandatoryIncludedKeywordsCheckMode(JobOfferFilterDto::KEYWORDS_CHECK_MODE_ANY);

        return $searchFilter;
    }

    /**
     * Since the {@see FilterDTO} and {@see JobOfferFilterDto} are using different strings for keywords rules
     * this function will translate the {@see FilterDTO} rule into {@see JobOfferFilterDto} rule
     *
     * @param KeywordsFilteringRule $filteringRule
     *
     * @return string|null
     */
    public static function filterDtoKeywordFilterRuleToJobSearchRule(KeywordsFilteringRule $filteringRule): ?string
    {
        return match ($filteringRule->value) {
            KeywordsFilteringRule::AND->value => JobOfferFilterDto::KEYWORDS_CHECK_MODE_ALL,
            KeywordsFilteringRule::OR->value  => JobOfferFilterDto::KEYWORDS_CHECK_MODE_ANY,
            default                           => null,
        };
    }

    /**
     * Since the {@see FilterDTO} and {@see JobOfferFilterDto} are using different strings for keywords rules
     * this function will translate the {@see JobOfferFilterDto} rule into {@see FilterDTO} rule
     *
     * @param string $jobOfferFilterKeywordMode
     * @return KeywordsFilteringRule
     */
    public static function jobSearchKeywordCheckModeToFrontFilterRule(string $jobOfferFilterKeywordMode): KeywordsFilteringRule
    {
        return match ($jobOfferFilterKeywordMode) {
            JobOfferFilterDto::KEYWORDS_CHECK_MODE_ALL => KeywordsFilteringRule::AND,
            JobOfferFilterDto::KEYWORDS_CHECK_MODE_ANY => KeywordsFilteringRule::OR,
        };
    }

    /**
     * Handles setting:
     * - {@see JobOfferFilterDto::$excludedKeywordsCheckMode} from {@see FilterDTO::$mandatoryExcludedKeywordsFilteringRule},
     * - {@see JobOfferFilterDto::$mandatoryIncludedKeywordsCheckMode} from {@see FilterDTO::$mandatoryIncludedKeywordsFilteringRule},
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     * @param FilterDTO         $filterDTO
     */
    private function setJobOfferFilterKeywordsModeFromFrontFilter(JobOfferFilterDto $jobOfferFilterDto, FilterDTO $filterDTO): void
    {
        if (
                !empty($filterDTO->getMandatoryExcludedKeywordsFilteringRule())
            &&  KeywordsFilteringRule::NONE->value !== $filterDTO->getMandatoryExcludedKeywordsFilteringRule()->value
        ) {
            $checkMode = $this->filterDtoKeywordFilterRuleToJobSearchRule($filterDTO->getMandatoryExcludedKeywordsFilteringRule());
            $jobOfferFilterDto->setExcludedKeywordsCheckMode($checkMode);
        }

        if (
                !empty($filterDTO->getMandatoryIncludedKeywordsFilteringRule())
            &&  KeywordsFilteringRule::NONE->value !== $filterDTO->getMandatoryIncludedKeywordsFilteringRule()->value
        ) {
            $checkMode = $this->filterDtoKeywordFilterRuleToJobSearchRule($filterDTO->getMandatoryIncludedKeywordsFilteringRule());
            $jobOfferFilterDto->setMandatoryIncludedKeywordsCheckMode($checkMode);
        }
    }

    /**
     * Handles setting:
     * - {@see FilterDTO::$mandatoryExcludedKeywordsFilteringRule} from {@see JobOfferFilterDto::$excludedKeywordsCheckMode},
     * - {@see FilterDTO::$mandatoryIncludedKeywordsFilteringRule} from {@see JobOfferFilterDto::$mandatoryIncludedKeywordsCheckMode},
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     * @param FilterDTO         $filterDTO
     */
    private function setFrontFilterKeywordsModeFromJobOfferFilter(JobOfferFilterDto $jobOfferFilterDto, FilterDTO $filterDTO): void
    {
        if (!empty($jobOfferFilterDto->getExcludedKeywords())) {
            $excludedFilterRule = $this->jobSearchKeywordCheckModeToFrontFilterRule($jobOfferFilterDto->getExcludedKeywordsCheckMode());
            $filterDTO->setMandatoryExcludedKeywordsFilteringRule($excludedFilterRule);
        }

        if (!empty($jobOfferFilterDto->getMandatoryIncludedKeywords())) {
            $includedFilterRule = $this->jobSearchKeywordCheckModeToFrontFilterRule($jobOfferFilterDto->getIncludedKeywordsCheckMode());
            $filterDTO->setMandatoryIncludedKeywordsFilteringRule($includedFilterRule);
        }
    }

    /**
     * Handles setting:
     * - {@see JobOfferFilterDto::$jobPostedDateTimeFilterDto} from {@see FilterDTO::$whenWasItPosted},
     *
     * @param JobOfferFilterDto $jobOfferFilterDto
     * @param FilterDTO         $filterDTO
     *
     * @throws Exception
     */
    private function setJobOfferDateFilterRuleFromFrontFilter(JobOfferFilterDto $jobOfferFilterDto, FilterDTO $filterDTO): void
    {
        if (
                !empty($filterDTO->getWhenWasItPosted())
            &&  PostedRule::NONE->value !== $filterDTO->getWhenWasItPosted()->value
        ) {
            $postedFilter = new JobPostedDateTimeFilterDto();
            $postedFilter->setSecondTimestamp((new DateTime())->getTimestamp());
            $postedFilter->setComparisonOperator(">");

            switch($filterDTO->getWhenWasItPosted()->value){
                case PostedRule::ONE_WEEK->value:
                    $secondTimestamp = (new DateTime())->modify("-7 DAYS")->getTimestamp();
                    break;

                case PostedRule::TWO_WEEK->value:
                    $secondTimestamp = (new DateTime())->modify("-14 DAYS")->getTimestamp();
                    break;

                case PostedRule::ONE_MONTH->value:
                    $secondTimestamp = (new DateTime())->modify("-31 DAYS")->getTimestamp();
                    break;
            }

            if (empty($secondTimestamp)) {
                $message = "
                    Second timestamp is not set. Should not happen, but phpstan keeps crying so added it. 
                    If it happened the there is something severely wrong
                ";
                throw new Exception($message);
            }

            $postedFilter->setFirstTimestamp($secondTimestamp);
            $postedFilter->validateSelf();

            $jobOfferFilterDto->setJobPostedDateTimeFilterDto($postedFilter);
        }
    }

}