<?php

namespace App\Service\Api\JobSearcher\Exclusion;

/**
 * Handles excluding certain offers manually
 */
class ManualOffersExclusionsService
{
    /**
     * Provides the hardcoded offers exclusions, it's dirty because it's just enough to work with
     * Has to be cleared out before go live
     *
     * @param array $offers
     * @return array
     */
    public function getExclusions(array $offers): array
    {
        // KEY is the company name, VALUE is the offer title, COMMENT is when application was made
        $alreadyAppliedOffersOfCompanies = [
           "Company name" => [
                "Offer"
            ],
        ];

        // these strings are used to exclude the companies by checking their names matching strings in here
        $excludedCompanyNameStrings = [

        ];

        $filteredOffers = [];
        foreach ($offers as $offer) {

            foreach ($excludedCompanyNameStrings as $exclusionString) {
                if (strstr($offer->companyDetail->companyName, $exclusionString)) {
                    continue 2;
                }
            }

            foreach ($alreadyAppliedOffersOfCompanies as $companyName => $jobTitles) {
                foreach ($jobTitles as $jobTitle) {
                    $normalizedCompanyName = trim(mb_strtolower($offer->companyDetail->companyName));
                    $normalizedJobTitle    = trim(mb_strtolower($offer->jobTitle));

                    $normalizedAppliedCompanyName = trim(mb_strtolower($companyName));
                    $normalizedAppliedJobTitle    = trim(mb_strtolower($jobTitle));
                    if (
                        $normalizedAppliedCompanyName === $normalizedCompanyName
                        && $normalizedAppliedJobTitle === $normalizedJobTitle
                    ) {
                        continue 3;
                    }
                }
            }

            if ($offer->analyzeStatus !== "ACCEPTED") {
                continue;
            }

            $filteredOffers[] = $offer;
        }


        return $filteredOffers;
    }

}