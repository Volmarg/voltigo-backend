<?php

namespace App\Service\RabbitMq;

use Exception;
use App\Constants\RabbitMq\Common\CommunicationConstants;

class AmqpService
{

    /**
     * Will attempt to extract the original message id that was sent from this project on "production"
     * as uniqueId.
     *
     * So from perspective of "producer" it's "uniqId",
     * From perspective of consumer it's "receivedUniqId" because the PRODUCER on OTHER side is bouncing it back,
     *
     * @param string $messageBody
     *
     * @return string|null
     * @throws Exception
     */
    public function extractOriginalMessageId(string $messageBody): ?string
    {
        $dataArray = json_decode($messageBody, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception("Provided message is not a valid json. Got message {$messageBody}. Json error" . json_last_error_msg());
        }

        $originalMessageId = $dataArray[CommunicationConstants::KEY_RECEIVED_UNIQUE_ID] ?? null;
        return $originalMessageId;
    }

}