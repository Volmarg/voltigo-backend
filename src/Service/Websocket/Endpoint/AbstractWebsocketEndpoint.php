<?php

namespace App\Service\Websocket\Endpoint;

use App\DTO\Internal\WebsocketConnectionDTO;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Exception\WebsocketException;
use App\Service\Logger\LoggerService;
use App\Service\Validation\ValidationService;
use App\Traits\Awareness\JwtAuthenticationServiceAwareTrait;
use Exception;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Common logic for endpoints
 */
abstract class AbstractWebsocketEndpoint
{
    use JwtAuthenticationServiceAwareTrait;

    /**
     * @var ?WebsocketConnectionDTO $connectionDTO
     */
    private ?WebsocketConnectionDTO $connectionDTO;

    /**
     * @var LoggerService $loggerService
     */
    protected LoggerService $loggerService;

    /**
     * @var ValidationService $validationService
     */
    protected ValidationService $validationService;

    /**
     * @var KernelInterface $kernel
     */
    protected KernelInterface $kernel;

    /**
     * @param LoggerService $loggerService
     */
    public function setLoggerService(LoggerService $loggerService): void
    {
        $this->loggerService = $loggerService;
    }

    /**
     * @param ValidationService $validationService
     */
    public function setValidationService(ValidationService $validationService): void
    {
        $this->validationService = $validationService;
    }

    /**
     * @return WebsocketConnectionDTO|null
     */
    public function getConnectionDTO(): ?WebsocketConnectionDTO
    {
        return $this->connectionDTO;
    }

    /**
     * @param WebsocketConnectionDTO|null $connectionDTO
     */
    public function setConnectionDTO(?WebsocketConnectionDTO $connectionDTO): void
    {
        $this->connectionDTO = $connectionDTO;
    }

    /**
     * @return KernelInterface
     */
    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    /**
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    /**
     * Handle validation of the incoming data
     *
     * @param string $msg
     * @return bool
     */
    abstract public function validate(string $msg): bool;

    /**
     * Executes the logic on incoming message
     */
    abstract protected function executeOnMessage(ConnectionInterface $from, string $msg): void;

    /**
     * Will execute logic upon getting a message
     * >> WARNING <<
     * This might lead to endless loop if backend will auto respond on front message
     *
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, string $msg): void
    {
        if( !$this->validate($msg) ){
            $this->loggerService->warning("Websocket endpoint data validation failed!", [
                "msg" => $msg,
            ]);

            $from->send("Invalid data has been sent to websocket! Closing connection!");
            $from->close();
            return;
        }

        $this->executeOnMessage($from, $msg);
    }

    /**
     * Will send message to frontend VIA socket, the way it works, is just that it takes the notification dto,
     * builds the json data suitable for frontend from it, and forwards it via found (existing) connection for user
     *
     * @param WebsocketNotificationDto $websocketNotificationFromBackendDto
     *
     * @return bool|null
     *         - true/false if connection exist
     *         - null if no connection is set
     */
    public function sendMessageToFrontend(WebsocketNotificationDto $websocketNotificationFromBackendDto): ?bool
    {
        if( empty($this->getConnectionDTO()) ){
            return null;
        }

        try{
            $this->connectionDTO->getClient()->send($websocketNotificationFromBackendDto->buildJsonWithDataPassedToFrontend());
        }catch(Exception | TypeError $e){
            $this->loggerService->logException($e);
            return false;
        }

        return true;
    }

    /**
     * Will execute method called on given endpoint (for example from frontend).
     * To be more accurate: frontend can set the name of the method to be called in backend for target endpoint,
     *
     * If the websocket endpoint has called method then it will be executed, else it will throw an exception,
     * Remember about using "see" phpdoc tag, to avoid removing "not used methods" since IDE won't know that
     * the endpoint based methods are magically called from within this method,
     *
     * @param WebsocketNotificationDto $notificationDto
     *
     * @throws WebsocketException
     * @uses AuthenticatedUserWebsocketEndpoint::refreshJwtToken()
     */
    protected function executeCalledMethod(WebsocketNotificationDto $notificationDto): void
    {
        if (!method_exists($this, $notificationDto->getActionName())) {
            throw new WebsocketException("Called method ({$notificationDto->getActionName()}) does not exist on given endpoint: " . $this::class);
        }

        try {
            $outgoingNotificationDto = $this->{$notificationDto->getActionName()}($notificationDto);
            $this->sendMessageToFrontend($outgoingNotificationDto);
        } catch (Exception|TypeError $e) {
            $message = "Error happened when calling endpoint method: {$notificationDto->getActionName()}. Error message: {$e->getMessage()}";
            $this->loggerService->logException($e);
            throw new WebsocketException($message);
        }
    }

}