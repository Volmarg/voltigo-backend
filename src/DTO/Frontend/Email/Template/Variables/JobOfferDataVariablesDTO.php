<?php

namespace App\DTO\Frontend\Email\Template\Variables;

use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;

/**
 * {@see JobOfferAnalyseResultDto} based variables that will be available in email template editor
 */
class JobOfferDataVariablesDTO
{
    /**
     * @var string $offerUrl
     */
    private string $offerUrl;

    /**
     * @var string $offerUrl
     */
    private string $offerTitle;

    /**
     * @var string $companyName
     */
    private string $companyName;

    /**
     * @var string $companyUrl
     */
    private string $companyUrl;

    /**
     * @return string
     */
    public function getOfferUrl(): string
    {
        return $this->offerUrl;
    }

    /**
     * @param string $offerUrl
     */
    public function setOfferUrl(string $offerUrl): void
    {
        $this->offerUrl = $offerUrl;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getCompanyUrl(): string
    {
        return $this->companyUrl;
    }

    /**
     * @param string $companyUrl
     */
    public function setCompanyUrl(string $companyUrl): void
    {
        $this->companyUrl = $companyUrl;
    }

    /**
     * @return string
     */
    public function getOfferTitle(): string
    {
        return $this->offerTitle;
    }

    /**
     * @param string $offerTitle
     */
    public function setOfferTitle(string $offerTitle): void
    {
        $this->offerTitle = $offerTitle;
    }

}