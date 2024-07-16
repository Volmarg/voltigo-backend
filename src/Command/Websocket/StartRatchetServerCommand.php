<?php

namespace App\Command\Websocket;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Service\Websocket\WebsocketServerConnectionHandler;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Helper command to test sending message to socket
 * ---> IMPORTANT <-----
 *
 * Whenever any underlying code, literally any (DI / service / controller / text) is changed
 * the server has to be restarted.
 *
 * Upon restarting - all the connections are being lost
 */
class StartRatchetServerCommand extends AbstractCommand
{
    const COMMAND_NAME = "start-websocket-server";

    /**
     * @var WebsocketServerConnectionHandler $websocketServerConnectionHandler
     */
    private WebsocketServerConnectionHandler $websocketServerConnectionHandler;

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will send single message to the websocket server");
    }

    public function __construct(
        ConfigLoader                     $configLoader,
        WebsocketServerConnectionHandler $websocketServerConnectionHandler,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->websocketServerConnectionHandler = $websocketServerConnectionHandler;
    }

    /**
     * Execute the command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        try{
            $this->io->info("Websocket server started");
            $this->websocketServerConnectionHandler->startServer();
        }catch(\Exception | \TypeError $e){
            $this->io->error("Message could not been sent");
            $this->io->info("Exception message: " . $e->getMessage());
            $this->io->info("Exception trace: " . $e->getTraceAsString());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}