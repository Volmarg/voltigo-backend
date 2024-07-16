<?php

namespace App\DTO\Frontend\Email\Template\Variables;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * All the fields in this dto will be available for user on front to use as template,
 * also the variables used on front template will be then resolved to real data from {@see JobOfferAnalyseResultDto}
 * through {@see VariableProvider::provideFromSearcherAnalysedOffer()}.
 *
 * So all the props in here are literally accessible for user in template builder
 */
class AllVariablesDTO
{

    /**
     * @var JobOfferDataVariablesDTO $jobOffer
     */
    private JobOfferDataVariablesDTO $jobOffer;

    /**
     * @var UserVariablesDTO $user
     */
    private UserVariablesDTO $user;

    /**
     * @return JobOfferDataVariablesDTO
     */
    public function getJobOffer(): JobOfferDataVariablesDTO
    {
        return $this->jobOffer;
    }

    /**
     * @param JobOfferDataVariablesDTO $jobOffer
     */
    public function setJobOffer(JobOfferDataVariablesDTO $jobOffer): void
    {
        $this->jobOffer = $jobOffer;
    }

    /**
     * @return UserVariablesDTO
     */
    public function getUser(): UserVariablesDTO
    {
        return $this->user;
    }

    /**
     * @param UserVariablesDTO $user
     */
    public function setUser(UserVariablesDTO $user): void
    {
        $this->user = $user;
    }

    /**
     * Returns this dto as array
     *
     * @return array
     */
    public function toArray(): array
    {
        $serializer = new Serializer([
            new ObjectNormalizer(),
        ], [
            new JsonEncoder()
        ]);

        $json  = $serializer->serialize($this, "json");
        $array = json_decode($json, true);

        return $array;
    }

}