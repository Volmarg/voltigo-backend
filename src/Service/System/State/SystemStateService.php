<?php

namespace App\Service\System\State;

use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\TypeProcessor\DateTimeProcessor;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class SystemStateService
{

    public function __construct(
        private readonly JobSearchService $jobSearchService,
        private readonly string $systemDisabledStartTime,
        private readonly string $systemDisabledEndTime,
        private readonly int $systemDisabledInfoBeforeMin,
        private readonly string $systemDisabledFilePath
    ){}

    private const COUNT_RUNNING_EXTRACTIONS_OFFSET = 2;

    /**
     * Safe, guard to prevent the handler wasting to many server resources or going down in general
     * Assuming:
     * > 11Gb Ram available for extractions,
     * > Single "full/all" extraction taking ~60Mb max,
     */
    private const MAX_RUNNING_EXTRACTIONS_QUOTA = 180;

    /**
     * Check if the offers searcher ({@see JobSearchService}) count of running extractions (offer searching instances)
     * have reached the max allowed quota
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function isOffersHandlerQuotaReached(): bool {
        $countRunningExtractions = $this->jobSearchService->getCountOfActiveSearches(self::COUNT_RUNNING_EXTRACTIONS_OFFSET);
        $isQuotaReached          = ($countRunningExtractions >= self::MAX_RUNNING_EXTRACTIONS_QUOTA);

        return $isQuotaReached;
    }

    /**
     * System being disabled is based on few things:
     * - current time: system goes off on that time to perform daily data updates, re-fetch etc. that's a MUST (it's around 4h window)
     * - existence of: "disabled" file, which forces the system to go into "disabled" (due to update / fixes etc.)
     *
     * @return bool
     *
     * @throws Exception
     */
    public function isSystemDisabled(): bool
    {
        return false; // for open source

        if ($this->isSystemDisabledViaFile()) {
            return true;
        }

        $nowTime          = (new \DateTime())->format("H:i:s");
        $nowMinutes       = DateTimeProcessor::timeIntoMinutes($nowTime);
        $startTimeMinutes = DateTimeProcessor::timeIntoMinutes($this->systemDisabledStartTime);
        $endTimeMinutes   = DateTimeProcessor::timeIntoMinutes($this->systemDisabledEndTime);

        return ($nowMinutes >= $startTimeMinutes && $nowMinutes <= $endTimeMinutes);
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function isSystemSoonGettingDisabled(): bool
    {
        return false; // for open source
        
        $nowTime          = (new \DateTime())->format("H:i:s");
        $nowMinutes       = DateTimeProcessor::timeIntoMinutes($nowTime);

        $disabledStartTimeMinutes = DateTimeProcessor::timeIntoMinutes($this->systemDisabledStartTime);
        $disabledEndTimeMinutes   = DateTimeProcessor::timeIntoMinutes($this->systemDisabledEndTime);

        $minMinutes = $disabledStartTimeMinutes - $this->systemDisabledInfoBeforeMin;
        $maxMinutes = $disabledEndTimeMinutes - $this->systemDisabledInfoBeforeMin;

        return ($nowMinutes >= $minMinutes && $nowMinutes <= $maxMinutes);
    }

    /**
     * @throws Exception
     */
    public function timeLeftTillSystemEnabled(): string
    {
        return DateTimeProcessor::countdownFormatTillTime($this->systemDisabledEndTime);
    }

    /**
     * @throws Exception
     */
    public function timeLeftTillSystemDisabled(): string
    {
        return DateTimeProcessor::countdownFormatTillTime($this->systemDisabledStartTime);
    }

    /**
     * Will set system into "disabled" mode when certain file exists in project.
     * This is needed for updating prod code etc. (will prevent users from doing anything at that moment - payments etc.).
     *
     * @return bool
     */
    public function isSystemDisabledViaFile(): bool
    {
        return false; // open-source
        return file_exists($this->systemDisabledFilePath);
    }

}