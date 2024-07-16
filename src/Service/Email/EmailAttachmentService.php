<?php

namespace App\Service\Email;

use App\Entity\Email\Email;
use App\Entity\Email\EmailAttachment;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailAttachmentService
{

    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ){}

    /**
     * Will prepare attachments for sending with email,
     * - will create copy of file that will be sent (needed because owner of the file can remove it meanwhile)
     *
     * @param string $locallyStoredFileName
     * @param string $absolutePathWithFileName
     * @param Email  $email
     *
     * @return EmailAttachment
     * @throws Exception
     */
    public function prepareAttachment(
        string       $locallyStoredFileName,
        string       $absolutePathWithFileName,
        Email        $email
    ): EmailAttachment
    {
        $attachmentsDir = $this->parameterBag->get("paths.email.attachments");
        $targetFolder   = $attachmentsDir . $email->getId() . DIRECTORY_SEPARATOR;
        $targetFilePath = $targetFolder . $locallyStoredFileName;

        if (!file_exists($targetFolder)) {
            $isDirCreated = @mkdir($targetFolder, 0755, true);
            if (!$isDirCreated) {
                throw new Exception("Could not create folder: {$targetFolder}. Reason: " . json_encode(error_get_last(), JSON_PRETTY_PRINT));
            }
        }

        if (file_exists($targetFilePath)) {
            throw new Exception("Tried to save the attachment for email of id: {$email->getId()}, but it already exists: {$targetFilePath}");
        }

        $isCopied = @copy($absolutePathWithFileName, $targetFilePath);
        if (!$isCopied) {
            $message = "Could not copy file from: {$absolutePathWithFileName}, to: {$targetFilePath}, reason: " . json_encode(error_get_last(), JSON_PRETTY_PRINT);
            throw new Exception($message);
        }

        $attachment = new EmailAttachment($targetFolder, $locallyStoredFileName);
        $attachment->setRemoveFile(true);

        return $attachment;
    }

}