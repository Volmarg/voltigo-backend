<?php

namespace App\Response\Job;

use App\Response\Base\BaseResponse;

/**
 * Handles sending the job applications from backend
 */
class GetJobApplications extends BaseResponse
{
    /**
     * @var array $jobApplications
     */
    private array $jobApplications;

    /**
     * @return array
     */
    public function getJobApplications(): array
    {
        return $this->jobApplications;
    }

    /**
     * @param array $jobApplications
     */
    public function setJobApplications(array $jobApplications): void
    {
        $this->jobApplications = $jobApplications;
    }

}