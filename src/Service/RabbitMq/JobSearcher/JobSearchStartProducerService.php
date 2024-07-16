<?php

namespace App\Service\RabbitMq\JobSearcher;

use App\DTO\RabbitMq\Producer\JobSearch\Start\ParameterBag;
use App\RabbitMq\Producer\JobSearcher\JobSearchStartProducer;
use App\Service\Serialization\ObjectSerializerService;

/**
 * Handles the {@see JobSearchStartProducer}, this is most like a wrapper, for providing the message
 */
class JobSearchStartProducerService
{
    public function __construct(
        private readonly ObjectSerializerService $objectSerializerService,
        private readonly JobSearchStartProducer $jobSearchProducer
    ){}

    /**
     * @param ParameterBag $parameterBag
     *
     * @return void
     */
    public function produce(ParameterBag $parameterBag): void
    {
        $json = $this->objectSerializerService->toJson($parameterBag);
        $this->jobSearchProducer->publish($json);
    }

}