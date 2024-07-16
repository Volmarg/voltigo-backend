<?php

namespace App\Service\Messages\Email;

use App\Response\Mail\GetMailStatusResponse;
use App\Response\Mail\InsertMailResponse;

/**
 * Defines common logic for E-Mails handling service
 */
interface EmailingServiceInterface
{
    /**
     * Returns the name of the used tool
     *
     * @return string
     */
    public function getToolName(): string;

    /**
     * Will call the EmailingService to insert the mail
     *
     * @param string        $subject
     * @param string        $body
     * @param array         $toMails
     * @param Array<string> $attachments - key is a file name, value is file content
     * @param string|null   $fromEmail
     *
     * @return InsertMailResponse
     */
    public function insertMail(string $subject, string $body, array $toMails, array $attachments = [], ?string $fromEmail = null): InsertMailResponse;

    /**
     * Will call the tool to get email sending status
     *
     * @param int $emailId
     */
    public function getEmailStatus(int $emailId): GetMailStatusResponse;
}