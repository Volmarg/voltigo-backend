<?php

namespace App\Service\Websocket;

use App\Service\Logger\LoggerService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Validation\ValidationService;
use App\Service\Websocket\Endpoint\AbstractWebsocketEndpoint;
use App\Service\Websocket\Endpoint\AuthenticatedUserWebsocketEndpoint;
use App\Service\Websocket\Endpoint\GlobalWebsocketEndpoint;
use App\Service\Websocket\Endpoint\NotFoundWebsocketEndpoint;
use App\Service\Websocket\Endpoint\NotificationWebsocketEndpoint;
use App\Service\Websocket\Endpoint\UnauthorizedWebsocketEndpoint;
use App\Traits\Awareness\JwtAuthenticationServiceAwareTrait;

/**
 * Handles the endpoints for websocket connections
 * This class is not added to global "Services" class as it's only used in the:
 * - {@see StartRatchetServerCommand}
 */
class WebsocketEndpointsHandler
{
    use JwtAuthenticationServiceAwareTrait;

    /**
     * If endpoint is provided in the websocket call then one of the mapped endpoints in here will be used
     * - In case of no endpoint being provided the global one is used {@see GlobalWebsocketEndpoint}
     * - In case of non-existing endpoint being called, the 404 one is used {@see NotFoundWebsocketEndpoint}
     */
    const MAP_ENDPOINT_URL_TO_CLASS_NAMESPACE = [
        GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME            => GlobalWebsocketEndpoint::class,
        NotFoundWebsocketEndpoint::SERVER_ENDPOINT_NAME          => NotFoundWebsocketEndpoint::class,
        UnauthorizedWebsocketEndpoint::SERVER_ENDPOINT_NAME      => UnauthorizedWebsocketEndpoint::class,
        NotificationWebsocketEndpoint::SERVER_ENDPOINT_NAME      => NotificationWebsocketEndpoint::class,
        AuthenticatedUserWebsocketEndpoint::SERVER_ENDPOINT_NAME => AuthenticatedUserWebsocketEndpoint::class,
    ];

    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;

    /**
     * @var ValidationService $validationService
     */
    private ValidationService $validationService;

    /**
     * @param LoggerService $loggerService
     * @param ValidationService $validationService
     */
    public function __construct(LoggerService $loggerService, ValidationService $validationService, JwtAuthenticationService $jwtAuthenticationService)
    {
        $this->jwtAuthenticationService = $jwtAuthenticationService;
        $this->loggerService            = $loggerService;
        $this->validationService        = $validationService;
    }

    /**
     * Will select endpoint based on the mapping:
     * - {@see WebsocketEndpointsHandler::MAP_ENDPOINT_URL_TO_CLASS_NAMESPACE}
     *
     * @param string|null $endpointName
     * @return AbstractWebsocketEndpoint
     */
    public function selectEndpoint(?string $endpointName = GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME): AbstractWebsocketEndpoint
    {
        /** @param AbstractWebsocketEndpoint $classNamespace */
        $classNamespace = GlobalWebsocketEndpoint::class;
        if( !is_null($endpointName) ){
            $classNamespace = self::MAP_ENDPOINT_URL_TO_CLASS_NAMESPACE[$endpointName] ?? NotFoundWebsocketEndpoint::class;
        }

        $websocketEndpoint = new $classNamespace();
        return $websocketEndpoint;
    }

}