<?php

namespace App\Service\Messages\Notification;

use App\Command\AbstractCommand;
use App\Command\Websocket\SendAsyncNotificationCommand;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Entity\Security\User;
use App\Service\ConfigLoader\ConfigLoaderProject;
use App\Service\System\State\SystemStateService;
use App\Service\Websocket\Endpoint\GlobalWebsocketEndpoint;
use App\Service\Websocket\WebsocketClientConnectionHandler;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles sending user notification with websocket
 */
class WebsocketNotificationService implements NotificationInterface
{
    public function __construct(
        private readonly ConfigLoaderProject $configLoaderProject,
        private readonly LoggerInterface     $logger,
        private readonly KernelInterface     $kernel,
        private readonly TranslatorInterface $translator,
        private readonly SystemStateService  $systemStateService
    ){}

    /**
     * {@inheritDoc}
     */
    public function sendNotification(WebsocketNotificationDto $notification, User $user): void
    {
        if (is_null($user->getId())) {
            throw new LogicException("Cannot send notification to not persisted user! Username identifier: {$user->getUserIdentifier()}");
        }

        $callback = function () use ($user, $notification): string {
            return $notification->toJson();
        };

        WebsocketClientConnectionHandler::sendDataToWebsocket($callback);
    }

    /**
     * {@see SendAsyncNotificationCommand} for more information
     *
     * This cannot be used
     * {@link https://symfony.com/doc/current/console/command_in_controller.html}
     * - because it's styl "SYNC" call
     *
     * Sadly this solution is also not working:
     * {@link https://symfony.com/doc/current/components/process.html#running-processes-asynchronously}
     *
     * The {@see chdir()} is needed in order to execute the command properly
     *
     * @param WebsocketNotificationDto $notification
     *
     * @throws Exception
     */
    public function sendAsyncNotification(WebsocketNotificationDto $notification): void
    {
        $oldDir = getcwd();
        chdir($this->kernel->getProjectDir());

        /** @see AbstractCommand::buildCommandName() */
        $trimmedProjectName = strtolower($this->configLoaderProject->getProjectName());
        $fullCommandName    = "php bin/console " . $trimmedProjectName . ":" . SendAsyncNotificationCommand::COMMAND_NAME;
        $calledCommand      = $fullCommandName
                            . " --" . SendAsyncNotificationCommand::OPTION_USER_ID            . "='{$notification->getUserIdToFindConnection()}'"
                            . " --" . SendAsyncNotificationCommand::OPTION_SOCKET_END_POINT   . "='{$notification->getSocketEndpointName()}'"
                            . " --" . SendAsyncNotificationCommand::OPTION_FIND_CONNECTION_BY . "='{$notification->getFindConnectionBy()}'"
                            . " --" . SendAsyncNotificationCommand::OPTION_MESSAGE            . "='{$notification->getMessage()}'"
                            . " --" . SendAsyncNotificationCommand::OPTION_HANDLER_NAME       . "='{$notification->getFrontendHandlerName()}'";

        if (!empty($notification->getActionName())) {
            $calledCommand .= " --" . SendAsyncNotificationCommand::OPTION_ACTION_NAME . "='{$notification->getActionName()}'";
        }

        $calledCommand .= " 2>&1"; // necessary to capture the error line

        exec($calledCommand, $output, $resultCode);
        chdir($oldDir);
        if ($resultCode != 0) {
            $this->logger->critical("Failed sending async notification.", [
                "resultCode" => $resultCode,
                'output'     => $output,
            ]);
        }
    }

    /**
     * @param WebsocketNotificationDto $incomingMessage
     * @param bool                     $knownTimeLeft
     *
     * @return WebsocketNotificationDto
     *
     * @throws Exception
     */
    public function buildSystemDisabledNotificationDto(WebsocketNotificationDto $incomingMessage, bool $knownTimeLeft = true): WebsocketNotificationDto
    {
        $message = $this->translator->trans('state.disabled.message', [
            '{{timeLeft}}' => $this->systemStateService->timeLeftTillSystemEnabled(),
        ]);

        if (!$knownTimeLeft) {
            $message = $this->translator->trans('state.disabled.downForMaintenance');
        }

        $notificationDto = new WebsocketNotificationDto();
        $notificationDto->setMessage($message);
        $notificationDto->setFrontendHandlerName(GlobalWebsocketEndpoint::FRONTEND_HANDLER_NAME);
        $notificationDto->setSocketEndpointName(GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME);
        $notificationDto->setFindConnectionBy(WebsocketNotificationDto::FIND_CONNECTION_BY_CONNECTION_ID);
        $notificationDto->setConnectionIdToFindExistingConnection($incomingMessage->getCurrentConnectionId());
        $notificationDto->setActionName(GlobalWebsocketEndpoint::FRONTEND_ACTION_SYSTEM_DISABLED);
        $notificationDto->setData([
            WebsocketNotificationDto::DATA_KEY_CAN_RESPOND        => false,
            WebsocketNotificationDto::DATA_KEY_IS_SYSTEM_DISABLED => true,
        ]);

        return $notificationDto;
    }

    /**
     * @param WebsocketNotificationDto $incomingMessage
     *
     * @return WebsocketNotificationDto
     *
     * @throws Exception
     */
    public function buildSystemSoonDisabledNotificationDto(WebsocketNotificationDto $incomingMessage): WebsocketNotificationDto
    {
        $message = $this->translator->trans('state.soonDisabled.message', [
            '{{timeLeft}}' => $this->systemStateService->timeLeftTillSystemDisabled(),
        ]);

        $notificationDto = new WebsocketNotificationDto();
        $notificationDto->setMessage($message);
        $notificationDto->setFrontendHandlerName(GlobalWebsocketEndpoint::FRONTEND_HANDLER_NAME);
        $notificationDto->setSocketEndpointName(GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME);
        $notificationDto->setFindConnectionBy(WebsocketNotificationDto::FIND_CONNECTION_BY_CONNECTION_ID);
        $notificationDto->setConnectionIdToFindExistingConnection($incomingMessage->getCurrentConnectionId());
        $notificationDto->setActionName(GlobalWebsocketEndpoint::FRONTEND_ACTION_SYSTEM_SOON_DISABLED);
        $notificationDto->setData([
            WebsocketNotificationDto::DATA_KEY_CAN_RESPOND             => false,
            WebsocketNotificationDto::DATA_KEY_IS_SYSTEM_SOON_DISABLED => true,
        ]);

        return $notificationDto;
    }
}