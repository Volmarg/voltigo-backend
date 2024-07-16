<?php

namespace App\Service\Websocket;

use App\Command\Websocket\StartRatchetServerCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Env;
use App\Controller\Core\Services;
use App\Controller\Security\UserController;
use App\DTO\Internal\WebsocketConnectionDTO;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Exception\LogicFlow\UnsupportedDataProvidedException;
use App\Service\Libs\Ratchet\RatchetConnectionDecorator;
use App\Service\Logger\LoggerService;
use App\Service\Messages\Notification\WebsocketNotificationService;
use App\Service\System\State\SystemStateService;
use App\Service\Websocket\Endpoint\AbstractWebsocketEndpoint;
use App\Service\Websocket\Endpoint\UnauthorizedWebsocketEndpoint;
use DateTime;
use Exception;
use Jfcherng\Utility\CliColor;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Websocket connection handler
 *
 * @WARNING Changing anything in here takes like TONES OF TIME. Literally TONES OF TIME.
 *          Whenever there is something to change, make sure that You have the time and nerves:
 *          - make a coffee,
 *          - prepare headache pills,
 *          - get like 4-6h of free time at least,
 *          - keep in mind that:
 *            - xdebug ain't working here due to nature of websockets, it was all built without xdebug to begin with
 *            - code is a bit dirty, due to issues with creating it at all, it should be cleaned up a bit, but each clean
 *              should be made in very little step, else this is time overkill
 *            - even renaming some variables / setters causes issues
 */
class WebsocketServerConnectionHandler implements MessageComponentInterface
{
    const KEY_SOURCE        = "source";
    const KEY_CONNECTION_ID = "connectionId";
    const KEY_USER_ID       = "userId";
    const KEY_STATUS        = "status";

    const KEY_STATUS_FAILURE = "failure";

    const KEY_SOURCE_FRONTEND = "frontend";
    const KEY_SOURCE_BACKEND  = "backend";

    const ALLOWED_SOURCES = [
        self::KEY_SOURCE_FRONTEND,
        self::KEY_SOURCE_BACKEND,
    ];

    /**
     * @var WebsocketConnectionDTO[] $clients
     */
    public array $clients = [];

    /**
     * @var WebsocketEndpointsHandler $websocketEndpointsHandler
     */
    private WebsocketEndpointsHandler $websocketEndpointsHandler;

    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * @var LoggerService $logger
     */
    private LoggerService $logger;

    /**
     * @param WebsocketEndpointsHandler    $websocketEndpointsHandler
     * @param ConfigLoader                 $configLoader
     * @param UserController               $userController
     * @param Services                     $services
     * @param KernelInterface              $kernel
     * @param SystemStateService           $systemStateService
     * @param WebsocketNotificationService $websocketNotificationService
     *
     * @throws UnsupportedDataProvidedException
     */
    public function __construct(
        WebsocketEndpointsHandler $websocketEndpointsHandler,
        ConfigLoader              $configLoader,
        UserController            $userController,
        Services                  $services,
        private readonly KernelInterface              $kernel,
        private readonly SystemStateService           $systemStateService,
        private readonly WebsocketNotificationService $websocketNotificationService
    ) {
        $this->websocketEndpointsHandler = $websocketEndpointsHandler;
        $this->userController            = $userController;
        $this->configLoader              = $configLoader;
        $this->services                  = $services;
        $this->logger                    = $services->getLoggerService()->setLoggerService(LoggerService::LOGGER_HANDLER_WEBSOCKET);
    }

    /**
     * Will start the server
     */
    public function startServer(): void
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this
                )
            ),
            Env::getWebsocketPort(),
        );
        $server->run();
    }

    /**
     * {@inheritDoc}
     * @param ConnectionInterface $from
     * @param string $msg
     * @throws UnsupportedDataProvidedException
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $userId    = null;
        $dataArray = json_decode($msg, true);
        if (!$this->services->getValidationService()->validateJson($msg)) {
            throw new Exception("Provided websocket message is not a valid json: {$msg}");
        }

        $websocketNotificationDto = WebsocketNotificationDto::fromJson($msg);

        $sourceName = $dataArray[self::KEY_SOURCE] ?? null; // todo: use the key from dto, remove that one here, leaving it like this for now due to socket issues
        if( in_array($sourceName, self::ALLOWED_SOURCES) ){
            $connectionIdentifier = $dataArray[self::KEY_CONNECTION_ID] ?? null;
            $userId               = $dataArray[self::KEY_USER_ID]       ?? null;

            if( empty($connectionIdentifier) ){
                throw new Exception("Connection identifier is missing! Did You forget to send that from frontend?");
            }

            $this->storeConnection($from, $connectionIdentifier, $userId);
        }else{
            $endpointName = UnauthorizedWebsocketEndpoint::SERVER_ENDPOINT_NAME;
            $websocketNotificationDto->setSocketEndpointName($endpointName);
        }


        // info: this is temporary dirty solution for calls from frontend, tried renaming the `userIdToFindConnection` but for now websocket dies if i do that - dunno, some cache?
        if (empty($websocketNotificationDto->getUserIdToFindConnection())) {
            $websocketNotificationDto->setUserIdToFindConnection((string)$userId); // info: that's dirty
        }

        $connectionDtos = $this->getConnectionsForDto($websocketNotificationDto);
        foreach ($connectionDtos as $connectionDto) {
            $endpoint = $this->initializeConnectedEndpoint($websocketNotificationDto, $connectionDto);

            // don't log pings as it will swarm the log file
            if ("ping" !== strtolower($websocketNotificationDto->getMessage(true))) {
                if(
                        json_last_error() === JSON_ERROR_NONE
                    &&  array_key_exists(self::KEY_STATUS, $dataArray)
                    &&  self::KEY_STATUS_FAILURE === $dataArray[self::KEY_STATUS]
                ){
                    self::connectionMessageWithFailure("[Front >>> Server] New message (FAILURE): ", $msg);
                } else {
                    self::connectionMessageWithSuccess("[Front >>> Server] New message (OK): ", $msg);
                }
            }

            $this->handleDisabledSystem($websocketNotificationDto, $endpoint);
            $endpoint->onMessage($from, $msg);
        }
    }

    /**
     * Handler for opened websocket connection
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        // check if any connections should be suspended - at best on each new connection to do it somewhat regular
        $this->suspendConnections();
        self::debugMessage("Count of active connections: " . count($this->clients));
        self::connectionMessageWithSuccess("Connection has been opened");
    }

    /**
     * Handler for closed websocket connection
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn): void
    {
        self::connectionMessageWithSuccess("Connection has been closed");
    }

    /**
     * Handler for websocket closed connection
     *
     * @param ConnectionInterface $conn
     * @param Throwable $e
     */
    public function onError(ConnectionInterface $conn, Throwable $e): void
    {
        self::errorMessage("Connection error: " . $e->getMessage());
    }

    /**
     * Will check if the websocket server is running.
     * This check is based on the `ps aux` listing and going over the entries to find the row that
     * matches the running command name.
     *
     * This method does not say if the server is reachable even if it runs, it only checks if the
     * process is there.
     *
     * In general if the server is running then it should be reachable because most likely
     * Ratchet would not even start the server or if connection would get broken the connection would've
     * been thrown and server would've gone down
     *
     * @return bool
     */
    public static function isWebsocketRunning(): bool
    {
        /**
         * Need to get list first and then loop over entries in php else the `docker` based shell execution
         * counts the pipe after the aux as standalone command, so it will always result some results even tho
         * the searched command would not be running
         */
        $psAuxResultString = shell_exec('ps aux');
        $psAuxResultArray  = explode("\n", $psAuxResultString);

        foreach ($psAuxResultArray as $psAuxLine) {
            if (str_contains($psAuxLine, StartRatchetServerCommand::COMMAND_NAME)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store connection on local array for reusing for communication with client,
     * Not replacing old connections on purpose because user might have multiple tabs open
     *
     * @param ConnectionInterface $connection
     * @param string $connectionIdentifier
     * @param string|null $userId
     */
    private function storeConnection(ConnectionInterface $connection, string $connectionIdentifier, ?string $userId = null): void
    {
        $connectionDecorator    = new RatchetConnectionDecorator($connection);
        $websocketConnectionDto = new WebsocketConnectionDTO();
        $websocketConnectionDto->setConnectionIdentifier($connectionIdentifier);
        $websocketConnectionDto->setClient($connectionDecorator);
        $websocketConnectionDto->setUserId($userId);

        $this->clients[$connectionIdentifier] = $websocketConnectionDto;
    }

    /**
     * @param WebsocketNotificationDto $websocketNotificationFromBackendDto
     *
     * @return WebsocketConnectionDTO[]
     */
    private function getConnectionsForDto(WebsocketNotificationDto $websocketNotificationFromBackendDto): array
    {
        if( $websocketNotificationFromBackendDto->isFindConnectionByUserId() ){
            return $this->getConnectionsForUserId($websocketNotificationFromBackendDto->getUserIdToFindConnection());
        }

        if ($websocketNotificationFromBackendDto->isFindConnectionByConnectionId()) {
            $connection =
                $this->clients[$websocketNotificationFromBackendDto->getConnectionIdToFindExistingConnection()] ?? null;

            if (!empty($connection)) {
                return [$connection];
            }

            return [];
        }

        return [];
    }

    /**
     * @param string|int $userId
     * @return WebsocketConnectionDTO[]
     */
    private function getConnectionsForUserId(string|int $userId): array
    {
        $connections = [];
        foreach ($this->clients as $connectionDTO) {
            if ($connectionDTO->getUserId() == $userId) {
                $connections[] = $connectionDTO;
            }
        }

        return $connections;
    }

    /**
     * Will remove the no longer necessary connections from the pool
     * - if connection consist of user (only such are saved) id and if so then checks if user has been active for last X minutes
     *
     * This will cause the frontend to lose the connection, and it might try to establish new one
     */
    private function suspendConnections(): void
    {
        $breakConnectionAndClientClosure = function(array &$clients, WebsocketConnectionDTO $connectionDto, string $index): void
        {
            $connectionDto->getClient()->close();
            unset($this->clients[$index]);
            self::debugMessage("Removing old connection");
        };

        $currentTimestamp = (new DateTime())->getTimestamp();
        foreach($this->clients as $index => $connectionDTO){

            $clonedConnectionTimeStamp          = clone $connectionDTO;
            $nonUserBasedConnectionMaxTimestamp = $clonedConnectionTimeStamp->getConnectionOpenDateTime()->modify(
                "+{$this->configLoader->getConfigLoaderWebSocket()->getNonUserBasedConnectionLifetimeMinutes()} MINUTES"
            )->getTimestamp();

            if(
                    empty($connectionDTO->getUserId())
                &&  $nonUserBasedConnectionMaxTimestamp <= $currentTimestamp
            ){
                $breakConnectionAndClientClosure($this->clients, $connectionDTO, $index);
                continue;
            }

            if( !empty($connectionDTO->getUserId()) ){
                $user = $this->userController->getOneById($connectionDTO->getUserId());

                // user might've been removed etc. so no longer care about the timestamp.
                if( empty($user) ){
                    $breakConnectionAndClientClosure($this->clients, $connectionDTO, $index);
                    continue;
                }

                $userActivity = (!is_null($user->getLastActivity()) ? clone $user->getLastActivity() : new DateTime());
                $userBasedConnectionMaxTimestamp = $userActivity->modify(
                    "+{$this->configLoader->getConfigLoaderWebSocket()->getUserBasedConnectionLifetimeMinutes()} MINUTES"
                )->getTimestamp();

                if($userBasedConnectionMaxTimestamp <= $currentTimestamp){
                    $breakConnectionAndClientClosure($this->clients, $connectionDTO, $index);
                }

            }

        }
    }

    /**
     * Will initialize / configure the endpoint.
     *
     * Not using the {@see AbstractWebsocketEndpoint::__construct()} as it's becoming problematic to let the
     * traits used in "ActionExecutor" access the services - instead, setting all necessary services in here
     *
     * Keep in min that the connectionDto can be null, for example:
     * - server starts for the first time and no frontend user has connected to socket
     *
     * @param WebsocketNotificationDto $notificationDto
     * @param WebsocketConnectionDTO   $connectionDto
     *
     * @return AbstractWebsocketEndpoint
     */
    private function initializeConnectedEndpoint(WebsocketNotificationDto $notificationDto, WebsocketConnectionDTO $connectionDto): AbstractWebsocketEndpoint
    {
        $endpointName = $notificationDto->getSocketEndpointName();

        $endpoint = $this->websocketEndpointsHandler->selectEndpoint($endpointName);
        $endpoint->setConnectionDTO($connectionDto);
        $endpoint->setJwtAuthenticationService($this->services->getJwtAuthenticationService());
        $endpoint->setLoggerService($this->logger);
        $endpoint->setValidationService($this->services->getValidationService());
        $endpoint->setKernel($this->kernel);

        return $endpoint;
    }

    /**
     * Connection based message formatting - success message
     * @link https://github.com/jfcherng/php-color-output
     *
     * @param string $message
     * @param string $additionalMessage
     */
    public static function connectionMessageWithSuccess(string $message, string $additionalMessage = ""): void
    {
        echo CliColor::color(self::getFormattedTimestampForMessage(), ["f_blue", "b"]);
        echo CliColor::color($message, ["f_green"]);
        echo CliColor::color($additionalMessage . PHP_EOL, ["f_cyan"]);
    }

    /**
     * Connection based message formatting - failure message
     * @link https://github.com/jfcherng/php-color-output
     *
     * @param string $message
     * @param string $additionalMessage
     */
    private static function connectionMessageWithFailure(string $message, string $additionalMessage = ""): void
    {
        echo CliColor::color(self::getFormattedTimestampForMessage(), ["f_blue", "b"]);
        echo CliColor::color($message, ["f_yellow"]);
        echo CliColor::color($additionalMessage . PHP_EOL, ["f_cyan"]);
    }

    /**
     * Debug based message formatting
     * @link https://github.com/jfcherng/php-color-output
     *
     * @param string $message
     */
    private static function debugMessage(string $message): void
    {
        echo CliColor::color(self::getFormattedTimestampForMessage(), ["f_blue", "b"]);
        echo CliColor::color($message . PHP_EOL, ["f_normal"]);
    }

    /**
     * Error based message formatting
     * @link https://github.com/jfcherng/php-color-output
     *
     * @param string $message
     */
    private static function errorMessage(string $message): void
    {
        echo CliColor::color(self::getFormattedTimestampForMessage(), ["f_blue", "b"]);
        echo CliColor::color($message . PHP_EOL, ["f_red"]);
    }

    /**
     * Return formatted timestamp for message
     *
     * @return string
     */
    private static function getFormattedTimestampForMessage(): string
    {
        $timeStamp = "[" . (new DateTime())->format("Y-m-d H:i:s") . "] ";
        return $timeStamp;
    }

    /**
     * If system is disabled then it will inform client about it
     * This works properly as long as sent message contains: {@see WebsocketNotificationDto::DATA_KEY_CAN_RESPOND} = false
     *
     * @throws Exception
     */
    private function handleDisabledSystem(WebsocketNotificationDto $websocketNotificationDto, AbstractWebsocketEndpoint $endpoint): void {
        if ($this->systemStateService->isSystemDisabledViaFile()) {
            $endpoint->sendMessageToFrontend($this->websocketNotificationService->buildSystemDisabledNotificationDto($websocketNotificationDto, false));
            return;
        }

        if ($this->systemStateService->isSystemDisabled()) {
            $endpoint->sendMessageToFrontend($this->websocketNotificationService->buildSystemDisabledNotificationDto($websocketNotificationDto));
            return;
        }

        if (!$this->systemStateService->isSystemDisabled() && $this->systemStateService->isSystemSoonGettingDisabled()) {
            $endpoint->sendMessageToFrontend($this->websocketNotificationService->buildSystemSoonDisabledNotificationDto($websocketNotificationDto));
            return;
        }
    }

}
