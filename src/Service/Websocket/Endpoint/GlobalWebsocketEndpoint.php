<?php

namespace App\Service\Websocket\Endpoint;

use Ratchet\ConnectionInterface;

/**
 * Global websocket endpoint for any connection
 */
class GlobalWebsocketEndpoint extends AbstractWebsocketEndpoint
{
    public const FRONTEND_ACTION_SYSTEM_DISABLED = "handleSystemDisabled";
    public const FRONTEND_ACTION_SYSTEM_SOON_DISABLED = "handleSystemSoonDisabled";

    const SERVER_ENDPOINT_NAME  = "global";
    const FRONTEND_HANDLER_NAME = "global";

    /**
     * {@inheritDoc}
     * @param ConnectionInterface $from
     * @param string $msg
     */
    protected function executeOnMessage(ConnectionInterface $from, string $msg): void
    {
        // nothing special to be done here now
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $msg): bool
    {
        return true;
    }
}