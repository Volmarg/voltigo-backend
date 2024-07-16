<?php

namespace App\Command\Websocket;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Security\UserController;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Service\Messages\Notification\WebsocketNotificationService;
use App\Service\Websocket\Endpoint\AuthenticatedUserWebsocketEndpoint;
use App\Service\Websocket\Endpoint\GlobalWebsocketEndpoint;
use App\Service\Websocket\Endpoint\NotFoundWebsocketEndpoint;
use App\Service\Websocket\Endpoint\NotificationWebsocketEndpoint;
use App\Service\Websocket\Endpoint\UnauthorizedWebsocketEndpoint;
use Exception;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Handles sending the notification asynchronously.
 * This command exists as solution for known issue:
 * - when consumer is running, then if it is about to send notification (via websocket) then the message hangs on,
 *
 * During searching for solution it was found out that notifications work fine if these are being sent in other process,
 * so this was taken as the solution for the issue since could not find anything else
 */
class SendAsyncNotificationCommand extends AbstractCommand
{
    const COMMAND_NAME = "websocket:send-async-message";

    private const SUPPORTED_WEBSOCKET_ENDPOINTS = [
      AuthenticatedUserWebsocketEndpoint::SERVER_ENDPOINT_NAME,
      GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME,
      UnauthorizedWebsocketEndpoint::SERVER_ENDPOINT_NAME,
      NotFoundWebsocketEndpoint::SERVER_ENDPOINT_NAME,
      NotificationWebsocketEndpoint::SERVER_ENDPOINT_NAME,
    ];

    private const SUPPORTED_FRONTEND_HANDLERS = [
        AuthenticatedUserWebsocketEndpoint::FRONTEND_HANDLER_NAME,
        GlobalWebsocketEndpoint::FRONTEND_HANDLER_NAME,
        NotificationWebsocketEndpoint::FRONTEND_HANDLER_NAME,
    ];

    private const SUPPORTED_CONNECTION_FINDING_WAYS = [
        WebsocketNotificationDto::FIND_CONNECTION_BY_CONNECTION_ID,
        WebsocketNotificationDto::FIND_CONNECTION_BY_USER_ID,
    ];

    public const OPTION_USER_ID            = "user-id";
    public const OPTION_SOCKET_END_POINT   = "end-point";
    public const OPTION_FIND_CONNECTION_BY = "find-connection-by";
    public const OPTION_MESSAGE            = "message";
    public const OPTION_HANDLER_NAME       = "handler-name";
    public const OPTION_ACTION_NAME        = "action-name";

    /**
     * @var WebsocketNotificationService $websocketNotificationService
     */
    private WebsocketNotificationService $websocketNotificationService;

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * @var int $userId
     */
    private int $userId;

    /**
     * @var string $socketEndPoint
     */
    private string $socketEndPoint;

    /**
     * @var string $findConnectionBy
     */
    private string $findConnectionBy;

    /**
     * @var string $message
     */
    private string $message;

    /**
     * @var string $handlerName
     */
    private string $handlerName;

    /**
     * @var string
     */
    private string $actionName = '';

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setHidden(true);
        $this->setDescription("Will send single websocket message to the user with given id");
        $this->addOption(self::OPTION_USER_ID            , null, InputOption::VALUE_REQUIRED, "Id of user to which message should forwarded be sent to");
        $this->addOption(self::OPTION_SOCKET_END_POINT   , null, InputOption::VALUE_REQUIRED, "Socket endpoint that will send this message");
        $this->addOption(self::OPTION_FIND_CONNECTION_BY , null, InputOption::VALUE_REQUIRED, "Way how the websocket connection should be looked for");
        $this->addOption(self::OPTION_MESSAGE            , null, InputOption::VALUE_REQUIRED, "Message to send on front");
        $this->addOption(self::OPTION_HANDLER_NAME       , null, InputOption::VALUE_REQUIRED, "Handler on frontend that will deal with the message from given end point");
        $this->addOption(self::OPTION_ACTION_NAME        , null, InputOption::VALUE_OPTIONAL, "Function name that will be triggered on front for this handler");
        $this->addUsage(
              "--" . self::OPTION_USER_ID ."=1 "
            . "--" . self::OPTION_SOCKET_END_POINT . "=notification "
            . "--" . self::OPTION_FIND_CONNECTION_BY . "=findConnectionByUserId "
            . "--" . self::OPTION_MESSAGE . "=test "
            . "--" . self::OPTION_HANDLER_NAME . "=notification "
            . "--" . self::OPTION_ACTION_NAME . "=doSomething "
        );
    }

    /**
     * @param ConfigLoader                 $configLoader
     * @param WebsocketNotificationService $websocketNotificationService
     * @param UserController               $userController
     * @param KernelInterface              $kernel
     */
    public function __construct(
        ConfigLoader                     $configLoader,
        WebsocketNotificationService     $websocketNotificationService,
        UserController                   $userController,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->userController               = $userController;
        $this->websocketNotificationService = $websocketNotificationService;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $this->userId           = $input->getOption(self::OPTION_USER_ID);
        $this->socketEndPoint   = $input->getOption(self::OPTION_SOCKET_END_POINT);
        $this->findConnectionBy = $input->getOption(self::OPTION_FIND_CONNECTION_BY);
        $this->message          = $input->getOption(self::OPTION_MESSAGE);
        $this->handlerName      = $input->getOption(self::OPTION_HANDLER_NAME);
        $this->actionName       = $input->getOption(self::OPTION_ACTION_NAME) ?: '';

        $this->validateInput();
    }

    /**
     * Execute the command logic
     *
     * @return int
     * @throws Exception
     */
    protected function executeLogic(): int
    {

        $userId = $this->input->getOption(self::OPTION_USER_ID);
        if( !is_numeric($userId) ){
            throw new Exception(self::OPTION_USER_ID . " is not numeric. Forgot to add the option?");
        }

        try {
            // todo: check empty message, not supported endpoint etc.
            $user = $this->userController->getOneById($userId);
            if (empty($user)) {
                throw new NotFoundHttpException("No user was found for id: {$userId}");
            }

            $notificationDto = new WebsocketNotificationDto();
            $notificationDto->setUserIdToFindConnection((string)$this->userId);
            $notificationDto->setSocketEndpointName($this->socketEndPoint);
            $notificationDto->setFindConnectionBy($this->findConnectionBy);
            $notificationDto->setMessage($this->message);
            $notificationDto->setFrontendHandlerName($this->handlerName);
            $notificationDto->setActionName($this->actionName);

            $this->websocketNotificationService->sendNotification($notificationDto, $user);
        } catch (Exception|TypeError $e) {
            $this->io->error("Websocket notification could not been sent");
            $this->io->info("Exception message: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Check if user input is valid
     */
    private function validateInput(): void
    {
        if (!in_array($this->socketEndPoint, self::SUPPORTED_WEBSOCKET_ENDPOINTS)) {
            $supportedList = json_encode(self::SUPPORTED_WEBSOCKET_ENDPOINTS);
            throw new LogicException("This socket endpoint is not supported. Got: {$this->socketEndPoint}, allowed are: " . $supportedList);
        }

        if (!in_array($this->handlerName, self::SUPPORTED_FRONTEND_HANDLERS)) {
            $supportedList = json_encode(self::SUPPORTED_FRONTEND_HANDLERS);
            throw new LogicException("This front handler is not supported. Got: {$this->handlerName}, allowed are: " . $supportedList);
        }

        if (!in_array($this->findConnectionBy, self::SUPPORTED_CONNECTION_FINDING_WAYS)) {
            $supportedList = json_encode(self::SUPPORTED_CONNECTION_FINDING_WAYS);
            throw new LogicException("This front handler is not supported. Got: {$this->handlerName}, allowed are: " . $supportedList);
        }

        if (empty($this->message)) {
            throw new LogicException("Message is empty");
        }
    }
}