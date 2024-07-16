<?php

namespace App\Service\Api\JobSearcher\Provider;

use Faker\Factory;
use Faker\Generator;
use JobSearcherBridge\DTO\Offers\CompanyDetailDto;
use JobSearcherBridge\DTO\Offers\ContactDetailDto;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;
use JobSearcherBridge\DTO\Offers\SalaryDto;

/**
 * Provides dummy, non-existing offer which can be used on front for rendering email template tags
 */
class DummyOfferProvider
{
    /**
     * @var Generator
     */
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * Return full offer dto with all the data set
     *
     * @return JobOfferAnalyseResultDto
     */
    public function provide(): JobOfferAnalyseResultDto
    {
        $dto = new JobOfferAnalyseResultDto();

        $this->fillBaseAnalyseDtoFields($dto);

        $salaryDto  = $this->buildSalary();
        $contactDto = $this->buildContactDetail();
        $companyDto = $this->buildCompanyDetail();

        $dto->setCompanyDetail($companyDto);
        $dto->setContactDetail($contactDto);
        $dto->setSalary($salaryDto);

        return $dto;
    }

    /**
     * Sets the simple properties (non object based) of the {@see JobOfferAnalyseResultDto}
     *
     * @param JobOfferAnalyseResultDto $dto
     *
     * @return void
     */
    private function fillBaseAnalyseDtoFields(JobOfferAnalyseResultDto $dto): void
    {
        $dto->setIdentifier($this->faker->numberBetween(1, 3000));
        $dto->setAppliedAt($this->faker->dateTime()->format("Y-m-d"));
        $dto->setHasHumanLanguages($this->faker->boolean());
        $dto->setHasLocation($this->faker->boolean());
        $dto->setHasJobDateTimePostedInformation($this->faker->boolean());
        $dto->setHasMail($this->faker->boolean());
        $dto->setHasPhone($this->faker->boolean());
        $dto->setHasSalary($this->faker->boolean());
        $dto->setHumanLanguages([$this->faker->languageCode()]);
        $dto->setJobDescription($this->faker->text(150));
        $dto->setJobOfferUrl($this->faker->url());
        $dto->setJobPostedDateTime($this->faker->dateTime()->format("Y-m-d"));
        $dto->setJobTitle($this->faker->jobTitle());
        $dto->setRemoteJobMentioned($this->faker->boolean());
    }

    /**
     * @return CompanyDetailDto
     */
    private function buildCompanyDetail(): CompanyDetailDto
    {
        $dto = new CompanyDetailDto();
        $dto->setAgeOld($this->faker->numberBetween(5, 10));
        $dto->setCompanyLocations(array_unique([$this->faker->city(), $this->faker->city(), $this->faker->city()]));
        $dto->setCompanyName($this->faker->company());
        $dto->setEmployeesRange("{$this->faker->numberBetween(10, 15)} - {$this->faker->numberBetween(20, 23)}");
        $dto->setLinkedinProfileUrl($this->faker->url());
        $dto->setWebsiteUrl($this->faker->url());

        return $dto;
    }

    /**
     * @return ContactDetailDto
     */
    private function buildContactDetail(): ContactDetailDto
    {
        $dto = new ContactDetailDto();

        $dto->setEmail($this->faker->companyEmail());
        $dto->setPhoneNumber($this->faker->phoneNumber());

        return $dto;
    }

    /**
     * @return SalaryDto
     */
    private function buildSalary(): SalaryDto
    {
        $dto = new SalaryDto();

        $dto->setSalaryAverage($this->faker->numberBetween(4000, 5000));
        $dto->setSalaryMin($this->faker->numberBetween(1000, 3000));
        $dto->setSalaryMax($this->faker->numberBetween(6000, 6500));

        return $dto;
    }
}