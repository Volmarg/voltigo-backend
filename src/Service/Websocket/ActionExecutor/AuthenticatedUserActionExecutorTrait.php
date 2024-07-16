<?php

namespace App\Service\Websocket\ActionExecutor;

use App\Controller\Security\UserController;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Exception\EmptyValueException;
use App\Service\Websocket\Endpoint\AuthenticatedUserWebsocketEndpoint;
use App\Traits\Awareness\JwtAuthenticationServiceAwareTrait;
use Exception;
use LogicException;

/**
 * Provides logic executable by websocket calls for {@see AuthenticatedUserWebsocketEndpoint}
 * Should only be used in {@see AuthenticatedUserWebsocketEndpoint}
 */
trait AuthenticatedUserActionExecutorTrait
{
    use JwtAuthenticationServiceAwareTrait;

    /**
     * Will take the user "jwtToken", refresh it, and build the {@see WebsocketNotificationDto} that can be sent back
     * to the client which requested the token
     *
     * @param WebsocketNotificationDto $incomingNotificationDto
     *
     * @return WebsocketNotificationDto
     *
     * @throws EmptyValueException
     * @throws Exception
     *
     * @see AbstractWebsocketEndpoint::executeCalledMethod()
     */
    protected function refreshJwtToken(WebsocketNotificationDto $incomingNotificationDto): WebsocketNotificationDto
    {
        $userController = $this->getKernel()->getContainer()->get(UserController::class);

        $this->assertJwtAuthenticationServiceSet();

        $jwtToken = $incomingNotificationDto->getDataParameter(WebsocketNotificationDto::DATA_KEY_JWT);
        if (empty($jwtToken)) {
            throw new EmptyValueException("Jwt token is not set");
        }

        if (is_null($this->getConnectionDTO()->getUserId())) {
            $message = "Something is wrong with this call, the userId is null, yet it should not happen, "
                     . "as this request should be coming from connection established for a user";
            throw new LogicException($message);
        }

        $refreshedToken          = $this->getJwtAuthenticationService()->handleJwtTokenRefresh($jwtToken);
        $outgoingNotificationDto = new WebsocketNotificationDto();
        $outgoingNotificationDto->setUserIdToFindConnection($this->getConnectionDTO()->getUserId());
        $outgoingNotificationDto->setDataParameter(WebsocketNotificationDto::DATA_KEY_JWT, $refreshedToken);
        $outgoingNotificationDto->setFrontendHandlerName(AuthenticatedUserWebsocketEndpoint::FRONTEND_HANDLER_NAME);
        $outgoingNotificationDto->setActionName(AuthenticatedUserWebsocketEndpoint::FRONTEND_ACTION_SET_FRESH_JWT_TOKEN);
        $outgoingNotificationDto->setMessage("Refreshed jwt token");

        $userController->updateUserActivityFromJwtToken($jwtToken);

        return $outgoingNotificationDto;
    }

}