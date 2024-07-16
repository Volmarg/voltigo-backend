<?php

namespace App\RabbitMq\Producer\JobSearcher;

use App\RabbitMq\Connection\QueueConnectionNames;
use App\RabbitMq\Producer\BaseProducer;

/**
 * @description dummy test producer, nobody cares about the message. It's just for testing if producing works
 */
class JobSearchStartProducer extends BaseProducer
{
    /**
     * {@inheritDoc}
     **/
    public function publish($msgBody, $routingKey = null, $additionalProperties = array(), array $headers = null): void
    {
        parent::publish($msgBody, QueueConnectionNames::JOB_OFFERS_HANDLER_DO_SEARCH);
    }

    /**
     * @return string
     */
    public function getTargetQueueName(): string
    {
        return QueueConnectionNames::JOB_OFFERS_HANDLER_DO_SEARCH;
    }

    /**
     * {@inheritDoc}
     */
    protected function isResponseExpected(): bool
    {
        return true;
    }

}