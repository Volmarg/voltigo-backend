<?php

namespace App\Service\Api\JobSearcher\Provider;

use App\Service\Api\JobSearcher\Exclusion\ManualOffersExclusionsService;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Originally this logic existed only for dummy purposes but eventually can be used for debugging purposes etc.
 * So not all the code is being added here
 */
class OffersFromFileProvider
{
    public function __construct(
        private ParameterBagInterface         $parameterBag,
        private ManualOffersExclusionsService $manualOffersExclusionsService
    ){}

    /**
     * Returns the job offers,
     *
     * @param array $excludedExternalIds
     *
     * @return array
     * @throws Exception
     */
    public function getOffersFromFile(array $excludedExternalIds = []): array
    {
        $filePath = $this->parameterBag->get('job_searcher.from_file_provider.file_path');
        if (!file_exists($filePath)) {
            throw new Exception("File with hardcoded offers does not exist. Path: {$filePath}");
        }

        $fileContent      = file_get_contents($filePath);
        $offersJson       = json_decode($fileContent, true);
        $offersArray      = array_values($offersJson);
        $arrayOfStdOffers = array_map(
            fn(array $data) => json_decode(json_encode($data)),
            $offersArray
        );

        $filteredOffers = array_filter(
            $arrayOfStdOffers,
            fn(stdClass $offer) => !in_array($offer->identifier, $excludedExternalIds)
        );

        $filteredOffers = array_values($filteredOffers);
        $filteredOffers = $this->manualOffersExclusionsService->getExclusions($filteredOffers);

        return $filteredOffers;
    }

}