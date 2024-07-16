<?php

namespace App\Service\Api\BlacklistHub;

use App\Service\Logger\LoggerService;
use BlacklistHubBridge\Dto\Email\EmailBlacklistSearchDto;
use BlacklistHubBridge\Exception\BlacklistHubBridgeException;
use BlacklistHubBridge\Request\Email\GetBlacklistingSingleEmailUrlRequest;
use BlacklistHubBridge\Request\Email\GetBlacklistStatusRequest;
use BlacklistHubBridge\Response\BaseResponse;
use BlacklistHubBridge\Response\Email\GetBlacklistStatusResponse;
use BlacklistHubBridge\Service\BridgeService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use TypeError;

/**
 * Handles communication with blacklist hub tool
 */
class BlacklistHubService
{
    /**
     * @param BridgeService $blacklistHubBridgeService
     * @param LoggerService $loggerService
     */
    public function __construct(
        private readonly BridgeService $blacklistHubBridgeService,
        private readonly LoggerService $loggerService,
    )
    {}

    /**
     * Will return Email statuses set in project,
     *
     * @param EmailBlacklistSearchDto[] $emailAddresses
     *
     * @throws GuzzleException
     * @throws BlacklistHubBridgeException
     */
    public function getEmailsStatuses(array $emailAddresses): GetBlacklistStatusResponse
    {
        // for open-source, blacklist hub is ignored
        $response = new GetBlacklistStatusResponse();
        $response->setSuccess(true);
        $response->setCode(Response::HTTP_OK);
        $response->setBlacklistCheckResults([]);

        return $response;

        try {
            $request = new GetBlacklistStatusRequest();
            $request->setEmailBlacklistSearchDtos($emailAddresses);

            $response = $this->blacklistHubBridgeService->getOffersForExtraction($request);

            if (!$response->isSuccess()) {
                $this->handleNotSuccessResponse($response);
            }

        } catch (Exception|TypeError $e) {
            $this->reThrowAsBlacklistException($e);
        }

        return $response;
    }

    /**
     * Will provide url for blocking single E-Mail address
     *
     * @param string      $recipient
     * @param string|null $fromEmail
     *
     * @return string
     * @throws BlacklistHubBridgeException
     * @throws GuzzleException
     */
    public function getBlacklistingSingleEmailUrl(string $recipient, ?string $fromEmail = null): string
    {
        // for open-source, blacklist hub is ignored
        return '';

        try {
            $request = new GetBlacklistingSingleEmailUrlRequest();
            $request->setRecipient($recipient);
            $request->setFromAddress($fromEmail);

            $response = $this->blacklistHubBridgeService->getBlacklistingSingleEmailUrl($request);
            if (!$response->isSuccess()) {
                $this->handleNotSuccessResponse($response);
            }

        } catch (Exception|TypeError $e) {
            $this->reThrowAsBlacklistException($e);
        }

        return $response->getUrl();
    }

    /**
     * Will throw any exception as blacklist exception
     *
     * @param Throwable $e
     *
     * @return never
     * @throws BlacklistHubBridgeException
     */
    private function reThrowAsBlacklistException(Throwable $e): never
    {
        $message = "
            OriginalMessage: {$e->getMessage()},
            OriginalTrace: {$e->getTraceAsString()}
        ";

        throw new BlacklistHubBridgeException($message);
    }

    /**
     * Will throw exception that indicates that call to the blacklist via bridge was not successful
     *
     * @return never
     *
     * @throws BlacklistHubBridgeException
     */
    private function throwNotSuccessResponse(): never
    {
        throw new BlacklistHubBridgeException("Response is NOT success");
    }

    /**
     * Will handle non success response from blacklist hub
     *
     * @param BaseResponse $baseResponse
     *
     * @return never
     * @throws BlacklistHubBridgeException
     */
    private function handleNotSuccessResponse(BaseResponse $baseResponse): never
    {
        $this->loggerService->warning("Response is NOT success", [
            "message" => $baseResponse->getMessage(),
            "code"    => $baseResponse->getCode(),
        ]);

        $this->throwNotSuccessResponse();
    }
}
