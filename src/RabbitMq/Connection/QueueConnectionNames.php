<?php

namespace App\RabbitMq\Connection;

use LogicException;

/**
 * Provides available queues
 */
class QueueConnectionNames
{
    public const JOB_OFFERS_HANDLER_DO_SEARCH = "job-offers-handler-do-search";
    public const JOB_OFFERS_HANDLER_TEST      = "test";

    public const ALL_QUEUES = [
        self::JOB_OFFERS_HANDLER_DO_SEARCH,
        self::JOB_OFFERS_HANDLER_TEST,
    ];

    /**
     * Check if provided queue is supported, but it still might be not reachable if the consumer is running under
     * different name
     *
     * @param string $queueName
     */
    public static function isQueueSupported(string $queueName): void
    {
        if (!in_array($queueName, self::ALL_QUEUES)) {
            throw new LogicException("Queue named ${$queueName} is not supported");
        }
    }
}