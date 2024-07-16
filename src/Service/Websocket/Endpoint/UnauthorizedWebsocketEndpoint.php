<?php

namespace App\Service\Websocket\Endpoint;

use Ratchet\ConnectionInterface;

/**
 * Websocket endpoint handler for unauthorized calls
 */
class UnauthorizedWebsocketEndpoint extends AbstractWebsocketEndpoint
{
    const SERVER_ENDPOINT_NAME = "unauthorized";

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    protected function executeOnMessage(ConnectionInterface $from, string $msg): void
    {
        $from->send("
            You are not authorized to call this websocket. Your attempt to communicate with websocket has been noted and will be proceed to administration!
        ");

        $this->loggerService->emergency("Got unauthorized call to the websocket", [
            "message" => $msg,
        ]);

        $from->close();
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $msg): bool
    {
        return true;
    }
}