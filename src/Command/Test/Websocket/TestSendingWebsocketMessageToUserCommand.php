<?php

namespace App\Command\Test\Websocket;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Security\UserController;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Service\Messages\Notification\WebsocketNotificationService;
use App\Service\Websocket\Endpoint\NotificationWebsocketEndpoint;
use Exception;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Helper command to test sending message from backend to the websocket server and forwarding it further on frontend to the user
 * It utilizes the: {@see WebsocketNotificationService} and {@see NotificationWebsocketEndpoint}
 * which send the message to frontend which is then shown as notification.
 */
class TestSendingWebsocketMessageToUserCommand extends AbstractCommand
{
    const COMMAND_NAME          = "test:send-websocket-message-to-user";
    const OPTION_USER_ID        = "user-id";
    const OPTIONS_USER_ID_SHORT = "uid";

    /**
     * @var WebsocketNotificationService $websocketNotificationService
     */
    private WebsocketNotificationService $websocketNotificationService;

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will send single websocket message to the user with given id");
        $this->addOption(self::OPTION_USER_ID, self::OPTIONS_USER_ID_SHORT, InputOption::VALUE_REQUIRED, "Id of user to which message should forwarded be sent to");
        $this->addUsage("--" . self::OPTION_USER_ID ."=1");
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
            $user = $this->userController->getOneById($userId);
            if (empty($user)) {
                throw new NotFoundHttpException("No user was found for id: {$userId}");
            }

            $notificationDto = new WebsocketNotificationDto();
            $notificationDto->setUserIdToFindConnection((string)$user->getId());
            $notificationDto->setSocketEndpointName(NotificationWebsocketEndpoint::SERVER_ENDPOINT_NAME);
            $notificationDto->setFindConnectionBy(WebsocketNotificationDto::FIND_CONNECTION_BY_USER_ID);
            $notificationDto->setMessage("This is a test message from websocket");
            $notificationDto->setFrontendHandlerName(NotificationWebsocketEndpoint::FRONTEND_HANDLER_NAME);

            $this->websocketNotificationService->sendNotification($notificationDto, $user);
        } catch (Exception|TypeError $e) {
            $this->io->error("Websocket notification could not been sent");
            $this->io->info("Exception message: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}