<?php

namespace App\Service\Websocket;

use App\Controller\Core\Env;
use Closure;
use Ratchet\Client;
use Ratchet\ConnectionInterface;
use React\Socket\ConnectionInterface as SocketConnection;

/**
 * Handles connecting to the socket as client but PHP is a client in this case
 * - allows passing forward data to front
 * @link https://github.com/ratchetphp/Pawl
 */
class WebsocketClientConnectionHandler
{
    /**
     * Will send data to websocket
     *
     * @param Closure $callable
     * @param bool $waitForResponse
     */
    public static function sendDataToWebsocket(Closure $callable, bool $waitForResponse = false): void
    {
        Client\connect(Env::getWebsocketConnectionUrl())->then( function($connection) use ($callable, $waitForResponse) {
            /**
             * Type hint must be skipped in function!
             * @var SocketConnection|ConnectionInterface $connection
             */
            if($waitForResponse){
                $connection->on('message', function($msg) use ($connection) {
                    echo "Received: {$msg}\n";
                    $connection->close();
                });
            }

            $message = $callable();
            $connection->send($message);
            if(!$waitForResponse){
                $connection->close();
            }
        }, function ($e) {
            echo "Could not connect: {$e->getMessage()}\n";
        });
    }

}