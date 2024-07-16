<?php

namespace App\Service\Libs\Ratchet;

use App\Service\Websocket\WebsocketServerConnectionHandler;
use Ratchet\ConnectionInterface;

/**
 * @description decorates the Ratchet connection,
 *              This decorator was added due to:
 *              - websocket server not intercepting the data sent via "client connection" directly,
 *                so whatever gets sent through direct client connection will not be logged in websocket server console,
 */
class RatchetConnectionDecorator implements ConnectionInterface
{
    public function __construct(
        private ConnectionInterface $connection
    ){}

    /**
     * {@inheritDoc}
     */
    function send($data): ConnectionInterface
    {
        WebsocketServerConnectionHandler::connectionMessageWithSuccess("[Server >>> Front] New message (OK): ", $data);
        return $this->connection->send($data);
    }

    /**
     * {@inheritDoc}
     */
    function close(): void
    {
        $this->connection->close();
    }
}