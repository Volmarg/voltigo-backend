<?php

namespace App\Service\Websocket\Endpoint;

use App\DTO\Internal\WebsocketNotificationDto;
use Ratchet\ConnectionInterface;

/**
 * Websocket handler for showing notifications to user
 */
class NotificationWebsocketEndpoint extends AbstractWebsocketEndpoint
{
    const SERVER_ENDPOINT_NAME  = "notification";
    const FRONTEND_HANDLER_NAME = "notification";

    /**
     * {@inheritDoc}
     * @param ConnectionInterface $from
     * @param string $msg
     */
    protected function executeOnMessage(ConnectionInterface $from, string $msg): void
    {
        $isValidJson = $this->validationService->validateJson($msg);
        if($isValidJson){
            $notificationDto = WebsocketNotificationDto::fromJson($msg);

            if( !empty($this->getConnectionDTO()) ){
                $this->sendMessageToFrontend($notificationDto);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $msg): bool
    {
        return true;
    }
}