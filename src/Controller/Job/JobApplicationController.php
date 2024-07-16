<?php

namespace App\Controller\Job;

use App\Controller\Core\ConfigLoader;
use App\Entity\Job\JobApplication;
use App\Entity\Security\User;
use App\Repository\Job\JobApplicationRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Controller for {@see JobApplication}
 */
class JobApplicationController
{

    public function __construct(
        private JobApplicationRepository $jobApplicationRepository,
        private ConfigLoader $configLoader
    ){}

    /**
     * Will either create new entity or update existing one
     *
     * @param JobApplication $jobApplication
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(JobApplication $jobApplication): void
    {
        $this->jobApplicationRepository->save($jobApplication);
    }

    /**
     * Will return all job applications made by user in given period
     *
     * @param User $user
     * @return JobApplication[]
     */
    public function findAllForUserInDaysInterval(User $user): array
    {
        $daysInterval = $this->configLoader->getConfigLoaderJobOffer()->getApplicationDaysPeriodSameOffer();
        return $this->jobApplicationRepository->findAllForUser($user, $daysInterval);
    }
}