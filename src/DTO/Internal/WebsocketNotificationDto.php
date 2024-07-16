<?php

namespace App\DTO\Internal;

use App\Service\Websocket\Endpoint\GlobalWebsocketEndpoint;
use App\Service\Websocket\WebsocketServerConnectionHandler;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dto used to pass data to websocket
 * @WARNING - like in description of the {@see WebsocketServerConnectionHandler}, changing any const KEY also causes connection issues
 */
class WebsocketNotificationDto
{
    public const DATA_KEY_JWT = "jwt";
    public const DATA_KEY_CAN_RESPOND = "canRespond";
    public const DATA_KEY_IS_SYSTEM_DISABLED = "isSystemDisabled";
    public const DATA_KEY_IS_SYSTEM_SOON_DISABLED = "isSystemSoonDisabled";

    const CALL_TYPE_FORWARD_TO_FRONTEND    = "forwardToFrontend";
    const FIND_CONNECTION_BY_USER_ID       = "findConnectionByUserId";
    const FIND_CONNECTION_BY_CONNECTION_ID = "finConnectionByConnectionId";

    const KEY_SOURCE                           = "source";
    const KEY_SOCKET_ENDPOINT_NAME             = "socketEndpointName";
    const KEY_FIND_CONNECTION_BY               = "findConnectionBy";
    const KEY_USER_ID_TO_FIND_CONNECTION       = "userIdToFindConnection";
    const KEY_CONNECTION_ID_TO_FIND_CONNECTION = "connectionIdToFindExistingConnection";
    const KEY_CALL_TYPE                        = "callType";

    /**
     * Keys forwarded directly to the frontend
     */
    const KEY_MESSAGE               = "message";
    const KEY_DATA                  = "data";
    const KEY_ACTION_NAME           = "actionName";
    const KEY_FRONTEND_HANDLER_NAME = "frontendHandlerName";

    /**
     * @var string $message
     */
    private string $message = "";

    /**
     * @var array $data
     */
    private array $data = [];

    /**
     * @var string $source
     */
    private string $source = WebsocketServerConnectionHandler::KEY_SOURCE_BACKEND;

    /**
     * @var string $actionName
     */
    private string $actionName = "";

    /**
     * @var string $frontendHandlerName
     */
    private string $frontendHandlerName = "";

    /**
     * @var string $socketEndpointName
     */
    private string $socketEndpointName = GlobalWebsocketEndpoint::SERVER_ENDPOINT_NAME;

    /**
     * @var string $callType
     */
    private string $callType = self::CALL_TYPE_FORWARD_TO_FRONTEND;

    /**
     * @var string $findConnectionBy
     */
    private string $findConnectionBy = self::FIND_CONNECTION_BY_USER_ID;

    /**
     * @var string $userIdToFindConnection
     */
    private string $userIdToFindConnection = "" ;

    /**
     * @var string $connectionIdToFindExistingConnection
     */
    private string $connectionIdToFindExistingConnection = "";

    /**
     * @var string $currentConnectionId
     */
    private string $currentConnectionId = "";

    public function __construct(){
        $this->currentConnectionId = uniqid();
    }

    /**
     * @param bool $allowEmptyString
     *
     * @return string
     */
    public function getMessage(bool $allowEmptyString = false): string
    {
        if (empty($this->message) && !$allowEmptyString) {
            throw new LogicException("Websocket notification dto message is empty!", Response::HTTP_BAD_REQUEST);
        }

        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * Check if connection is coming from backend
     *
     * @return bool
     */
    public function isConnectionFromBackend(): bool
    {
        return ($this->getSource() === WebsocketServerConnectionHandler::KEY_SOURCE_BACKEND);
    }

    /**
     * Check if connection is coming from frontend
     *
     * @return bool
     */
    public function isConnectionFromFrontend(): bool
    {
        return ($this->getSource() === WebsocketServerConnectionHandler::KEY_SOURCE_FRONTEND);
    }

    /**
     * Will yield value from the delivered data {@see WebsocketNotificationDto::KEY_DATA}
     * or null if such key exists in the array
     *
     * @param string $keyName
     *
     * @return mixed
     */
    public function getDataParameter(string $keyName): mixed
    {
        return $this->data[$keyName] ?? null;
    }

    /**
     * Will set the value under key in data bag.
     * If the key already exists then it gets overwritten.
     *
     * @param string $keyName
     * @param mixed  $value
     *
     * @return void
     */
    public function setDataParameter(string $keyName, mixed $value): void
    {
        $this->data[$keyName] = $value;
    }

    /**
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * @param string $actionName
     */
    public function setActionName(string $actionName): void
    {
        $this->actionName = $actionName;
    }

    /**
     * @return string
     */
    public function getSocketEndpointName(): string
    {
        return $this->socketEndpointName;
    }

    /**
     * @param string $socketEndpointName
     */
    public function setSocketEndpointName(string $socketEndpointName): void
    {
        $this->socketEndpointName = $socketEndpointName;
    }

    /**
     * @return string
     */
    public function getCallType(): string
    {
        return $this->callType;
    }

    /**
     * @param string $callType
     */
    public function setCallType(string $callType): void
    {
        $this->callType = $callType;
    }

    /**
     * @return string
     */
    public function getFindConnectionBy(): string
    {
        return $this->findConnectionBy;
    }

    /**
     * @param string $findConnectionBy
     */
    public function setFindConnectionBy(string $findConnectionBy): void
    {
        $this->findConnectionBy = $findConnectionBy;
    }

    /**
     * @return string
     */
    public function getUserIdToFindConnection(): string
    {
        return $this->userIdToFindConnection;
    }

    /**
     * @param string $userIdToFindConnection
     */
    public function setUserIdToFindConnection(string $userIdToFindConnection): void
    {
        $this->userIdToFindConnection = $userIdToFindConnection;
    }

    /**
     * @return string
     */
    public function getConnectionIdToFindExistingConnection(): string
    {
        return $this->connectionIdToFindExistingConnection;
    }

    /**
     * @param string $connectionIdToFindExistingConnection
     */
    public function setConnectionIdToFindExistingConnection(string $connectionIdToFindExistingConnection): void
    {
        $this->connectionIdToFindExistingConnection = $connectionIdToFindExistingConnection;
    }

    /**
     * @return string
     */
    public function getCurrentConnectionId(): string
    {
        return $this->currentConnectionId;
    }

    /**
     * @param string $currentConnectionId
     */
    public function setCurrentConnectionId(string $currentConnectionId): void
    {
        $this->currentConnectionId = $currentConnectionId;
    }

    /**
     * Check if the connection finding should be handled by searching for connection of given id
     *
     * @return bool
     */
    public function isFindConnectionByConnectionId(): bool
    {
        return ($this->findConnectionBy === self::FIND_CONNECTION_BY_CONNECTION_ID);
    }

    /**
     * Check if the connection finding should be handled by searching for connection for given user id
     *
     * @return bool
     */
    public function isFindConnectionByUserId(): bool
    {
        return ($this->findConnectionBy === self::FIND_CONNECTION_BY_USER_ID);
    }

    /**
     * @return string
     */
    public function getFrontendHandlerName(): string
    {
        return $this->frontendHandlerName;
    }

    /**
     * @param string $frontendHandlerName
     */
    public function setFrontendHandlerName(string $frontendHandlerName): void
    {
        $this->frontendHandlerName = $frontendHandlerName;
    }

    /**
     * Returns data as json
     *
     * @return string
     */
    public function toJson(): string
    {
        $dataArray = [
            self::KEY_SOURCE                                    => $this->getSource(),
            self::KEY_ACTION_NAME                               => $this->getActionName(),
            self::KEY_SOCKET_ENDPOINT_NAME                      => $this->getSocketEndpointName(),
            self::KEY_FIND_CONNECTION_BY                        => $this->getFindConnectionBy(),
            self::KEY_USER_ID_TO_FIND_CONNECTION                => $this->getUserIdToFindConnection(),
            self::KEY_CONNECTION_ID_TO_FIND_CONNECTION          => $this->getConnectionIdToFindExistingConnection(),
            self::KEY_CALL_TYPE                                 => $this->getCallType(),
            WebsocketServerConnectionHandler::KEY_CONNECTION_ID => $this->getCurrentConnectionId(),
            self::KEY_DATA                                      => $this->getData(),
            self::KEY_MESSAGE                                   => $this->getMessage(),
            self::KEY_FRONTEND_HANDLER_NAME                     => $this->getFrontendHandlerName(),
        ];

        return json_encode($dataArray);
    }

    /**
     * Will build dto from json string
     *
     * @param string $json
     *
     * @return WebsocketNotificationDto
     */
    public static function fromJson(string $json): WebsocketNotificationDto
    {
        $dataArray = json_decode($json, true);

        $source                               = $dataArray[self::KEY_SOURCE]                                    ?? "";
        $actionName                           = $dataArray[self::KEY_ACTION_NAME]                               ?? "";
        $socketEndpointName                   = $dataArray[self::KEY_SOCKET_ENDPOINT_NAME]                      ?? "";
        $findConnectionBy                     = $dataArray[self::KEY_FIND_CONNECTION_BY]                        ?? self::FIND_CONNECTION_BY_USER_ID; #This is a must, otherwise for now the frontend message won't find existing connection
        $userId                               = $dataArray[self::KEY_USER_ID_TO_FIND_CONNECTION]                ?? "";
        $connectionIdToFindExistingConnection = $dataArray[self::KEY_CONNECTION_ID_TO_FIND_CONNECTION]          ?? "";
        $callType                             = $dataArray[self::KEY_CALL_TYPE]                                 ?? "";
        $data                                 = $dataArray[self::KEY_DATA]                                      ?? [];
        $message                              = $dataArray[self::KEY_MESSAGE]                                   ?? "";
        $frontendHandlerName                  = $dataArray[self::KEY_FRONTEND_HANDLER_NAME]                     ?? "";
        $currentConnectionId                  = $dataArray[WebsocketServerConnectionHandler::KEY_CONNECTION_ID] ?? "";

        $dto = new WebsocketNotificationDto();
        $dto->setSource($source);
        $dto->setActionName($actionName);
        $dto->setFrontendHandlerName($frontendHandlerName);
        $dto->setSocketEndpointName($socketEndpointName);
        $dto->setFindConnectionBy($findConnectionBy);
        $dto->setUserIdToFindConnection($userId);
        $dto->setConnectionIdToFindExistingConnection($connectionIdToFindExistingConnection);
        $dto->setCallType($callType);
        $dto->setCurrentConnectionId($currentConnectionId);
        $dto->setMessage($message);
        $dto->setData($data);

        return $dto;
    }

    /**
     * Will build json string which contains the data that is passed to the frontend
     */
    public function buildJsonWithDataPassedToFrontend(): string
    {
        $data = [
            self::KEY_MESSAGE               => $this->getMessage(),
            self::KEY_DATA                  => $this->getData(),
            self::KEY_ACTION_NAME           => $this->getActionName(),
            self::KEY_FRONTEND_HANDLER_NAME => $this->getFrontendHandlerName(),
        ];

        return json_encode($data);
    }

}