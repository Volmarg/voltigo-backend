<?php

namespace App\Service\Api\MessageHub;

use App\Controller\Core\ConfigLoader;
use App\Exception\EmptyValueException;
use App\Response\Mail\InsertMailResponse;
use App\Service\Logger\LoggerService;
use App\Service\Messages\Email\EmailingServiceInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use TypeError;
use App\DTO\Mail\MailDTO;
use App\Request\Mail\GetMailStatusRequest;
use App\Request\Mail\InsertMailRequest;
use App\Response\Mail\GetMailStatusResponse;
use App\MessageHubBridge;

/**
 * Handles sending messages
 */
class MessageHubService implements EmailingServiceInterface
{
    /**
     * If this is changed then strings in DB must most likely also need to be changed
     *
     * @return string
     */
    public function getToolName(): string
    {
        return "Message Hub";
    }

    /**
     * @param MessageHubBridge $messageHubBridge
     * @param LoggerService $loggerService
     * @param ConfigLoader $configLoader
     */
    public function __construct(
        private readonly MessageHubBridge $messageHubBridge,
        private readonly LoggerService    $loggerService,
        private readonly ConfigLoader     $configLoader
    )
    {}

    /**
     * Will call the Hub to insert the mail
     *
     * @param string      $subject
     * @param string      $body
     * @param array       $toMails
     * @param array       $attachments
     * @param string|null $fromEmail
     *
     * @return InsertMailResponse
     * @throws GuzzleException
     * @throws EmptyValueException
     */
    public function insertMail(string $subject, string $body, array $toMails, array $attachments = [], ?string $fromEmail = null): InsertMailResponse
    {
        $request  = new InsertMailRequest();
        $mailDto  = new MailDTO();

        $mailDto->setBody($body);
        $mailDto->setSubject($subject);
        $mailDto->setSource($this->configLoader->getConfigLoaderProject()->getProjectName());
        $mailDto->setFromEmail($fromEmail ?? $this->configLoader->getConfigLoaderProject()->getFromMail());
        $mailDto->setToEmails($toMails);
        $mailDto->setAttachments($attachments);
        $mailDto->setEmailType(MailDTO::TYPE_PLAIN);
        $mailDto->setTrackOpenState(false);

        $mailDto->validateSelf();

        $request->setMailDto($mailDto);

        try {
            $response = $this->messageHubBridge->insertMail($request);
        } catch (Exception | TypeError $e) {
            $this->loggerService->logException($e);
            throw $e;
        }

        return $response;
    }

    /**
     * Will call the Hub to get email sending status
     *
     * @param int $emailId
     * @return GetMailStatusResponse
     * @throws GuzzleException
     */
    public function getEmailStatus(int $emailId): GetMailStatusResponse
    {
        $request  = new GetMailStatusRequest($emailId);

        try {
            $response = $this->messageHubBridge->getMailStatus($request);
        } catch (Exception | TypeError $e) {
            $this->loggerService->logException($e);
            throw $e;
        }

        return $response;
    }

}
