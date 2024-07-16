<?php

namespace App\Service\Websocket\Endpoint;

use App\DTO\Internal\WebsocketNotificationDto;
use App\Exception\WebsocketException;
use App\Service\Websocket\ActionExecutor\AuthenticatedUserActionExecutorTrait;
use App\Service\Websocket\WebsocketServerConnectionHandler;
use Ratchet\ConnectionInterface;

/**
 * Websocket endpoint handler for authenticated user
 */
class AuthenticatedUserWebsocketEndpoint extends AbstractWebsocketEndpoint
{
    use AuthenticatedUserActionExecutorTrait;

    const SERVER_ENDPOINT_NAME  = "AuthenticatedUser";
    const FRONTEND_HANDLER_NAME = "AuthenticatedUser";

    /**
     * These methods are getting called on FRONT by BACKEND
     */
    const FRONTEND_ACTION_LOGOUT_USER_AND_INVALIDATE_TOKEN = "logoutUserAndInvalidateToken";
    const FRONTEND_ACTION_SET_FRESH_JWT_TOKEN              = "setFreshJwtToken";
    public const FRONTEND_ACTION_HANDLE_POINTS_UPDATE = "handlePointsUpdate";

   /**
    * {@inheritDoc}
    */
   public function validate(string $msg): bool
   {
        $dataArray = json_decode($msg, true);
        if( JSON_ERROR_NONE !== json_last_error() ){
            return false;
        }

        $source = $dataArray[WebsocketServerConnectionHandler::KEY_SOURCE] ?? null;

        if(
                WebsocketServerConnectionHandler::KEY_SOURCE_FRONTEND === $source
            &&  !array_key_exists(WebsocketServerConnectionHandler::KEY_USER_ID, $dataArray)
        ){
            return false;
        }

        return true;
   }

    /**
     * {@inheritDoc}
     *
     * @param ConnectionInterface $from
     * @param string              $msg
     *
     * @throws WebsocketException
     */
    protected function executeOnMessage(ConnectionInterface $from, string $msg): void
    {
        $isValidJson = $this->validationService->validateJson($msg);
        if($isValidJson){
            $notificationDto = WebsocketNotificationDto::fromJson($msg);
            if ($notificationDto->isConnectionFromFrontend()) {

                if (!empty($notificationDto->getActionName())) {
                    $this->executeCalledMethod($notificationDto);
                }

                return;
            }


            if(
                    !empty($this->getConnectionDTO())
                &&  !empty($notificationDto->getActionName())
            ){
                $this->sendMessageToFrontend($notificationDto);
            }
        }

    }

}