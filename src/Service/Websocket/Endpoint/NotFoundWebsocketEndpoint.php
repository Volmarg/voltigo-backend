<?php

namespace App\Service\Websocket\Endpoint;

use Ratchet\ConnectionInterface;

/**
 * Websocket handler for non existing endpoint
 */
class NotFoundWebsocketEndpoint extends AbstractWebsocketEndpoint
{
    const SERVER_ENDPOINT_NAME = "NotFound";

    /**
     * {@inheritDoc}
     * @param ConnectionInterface $from
     * @param string $msg
     */
    protected function executeOnMessage(ConnectionInterface $from, string $msg): void
    {
        $from->send("Requested endpoint doest not exist");
        $this->loggerService->info("Called non existing websocket endpoint", [
            "message" => $msg,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $msg): bool
    {
        return true;
    }
}