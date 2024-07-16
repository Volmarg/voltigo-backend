<?php

namespace App\Service\Email;

use App\Command\Email\EmailSenderCommand;
use App\Controller\Email\EmailController;
use App\DTO\Validation\ValidationResultDTO;
use App\Entity\Email\Email;
use App\Entity\File\UploadedFile;
use App\Exception\LogicFlow\Email\Modifier\EmailModifierException;
use App\Repository\Email\EmailRepository;
use App\Repository\File\UploadedFileRepository;
use App\Service\Api\BlacklistHub\BlacklistHubService;
use App\Service\Messages\Email\EmailingServiceInterface;
use App\Service\Serialization\ObjectSerializerService;
use BlacklistHubBridge\Dto\Email\EmailBlacklistSearchDto;
use BlacklistHubBridge\Exception\BlacklistHubBridgeException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * @description contains logic explicitly for {@see EmailSenderCommand}
 */
class EmailSenderService
{
    public function __construct(
        private readonly BlacklistHubService      $blacklistHubService,
        private readonly ObjectSerializerService  $objectSerializerService,
        private readonly EmailRepository          $emailRepository,
        private readonly LoggerInterface          $logger,
        private readonly EmailModifierService     $emailModifierService,
        private readonly EmailingServiceInterface $emailingService,
        private readonly EmailController          $emailController,
        private readonly UploadedFileRepository   $uploadedFileRepository,
        private readonly EntityManagerInterface   $entityManager
    ){}

    /**
     * Will validate the recipients
     *
     * @return Array<ValidationResultDTO>
     * @param Array<string> $recipients
     * @param Email           $email
     *
     * @throws BlacklistHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function validateRecipients(array $recipients, Email $email): array
    {
        $allErrors = [];
        foreach ($recipients as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $validationDto = new ValidationResultDTO();
                $validationDto->setSuccess(false);
                $validationDto->setViolationsWithMessages([
                    "invalidEmail" => "At least one of the E-Mails is synthetically invalid (recipient: {$recipient}, email id: {$email->getId()})",
                ]);

                $jsonValidationError = $this->objectSerializerService->toJson($validationDto);
                $allErrors[]         = $validationDto;

                $email->setError($jsonValidationError);
                $email->setStatus(Email::KEY_INVALID_RECIPIENT);
                $this->emailRepository->save($email);

                $this->logger->warning("E-Mail (Id: {$email->getId()}), will not be sent: " . Email::KEY_INVALID_RECIPIENT);

                break;
            }

            $emailBlacklistSearch = new EmailBlacklistSearchDto($recipient, $email->getSender()?->getEmail());

            $response = $this->blacklistHubService->getEmailsStatuses([$emailBlacklistSearch]);
            if ($response->isRecipientBlacklisted($recipient)) {
                $status = "Blacklisted recipient: {$recipient}";

                $validationResultDto = new ValidationResultDTO();
                $validationResultDto->setSuccess(false);
                $validationResultDto->setMessage($status);

                $allErrors[] = $validationResultDto;

                $email->setError($status);
                $email->setStatus(Email::KEY_BLOCKED_BLACKLISTED_RECIPIENT);
                $this->emailRepository->save($email);

                $this->logger->warning("E-Mail (Id: {$email->getId()}), will not be sent: " . $status);

                break;
            }

        }

        return $allErrors;
    }

    /**
     * Will handle sending E-Mail
     *
     * @param Email         $email
     * @param Array<string> $recipients
     * @param bool          $isCopy
     *
     * @return bool - true = success, failure otherwise
     *
     * @throws ORMException
     * @throws EmailModifierException
     */
    public function handleEmailSending(Email $email, array $recipients, bool $isCopy = false): bool
    {
        $attachments = $this->buildAttachments($email);
        $this->updateUsedUploadedFiles($email);

        /**
         * Decided that the E-Mail modification will be applied directly before sending,
         * E-Mail will be accessible in Message Hub, original user based E-Mail stays here
         */
        $usedBody = $email->getBody();
        if ($email->isBuilderTemplateBased() && !$email->isTemplateTestEmail()) {
            $this->emailModifierService->setBlockingUrl(!$isCopy);
            $usedBody = $this->emailModifierService->applyChanges($email);
        }

        $response = $this->emailingService->insertMail(
            $email->getSubject(),
            $usedBody,
            $recipients,
            $attachments,
            $email->getSender()?->getEmail()
        );

        $hasExternalId = !empty($email->getExternalId());
        if (!$isCopy) {
            $email->setToolName($this->emailingService->getToolName());
        }

        if ($response->isSuccess()) {
            $message = "E-Mail (Id: {$email->getId()}) has been sent";
            if (!$isCopy) {
                $email->setExternalId($response->getId());
                $email->setStatus(Email::KEY_STATUS_TRANSFERRED_TO_EXTERNAL_TOOL);
            }

            if ($isCopy) {
                $message = "Copy of " . $message;
            }

            $this->logger->info($message);
        } else {
            $this->logger->critical("Tried to send E-Mail with id {$email->getId()}. Api was reached, but response was NOT success", [
                "emailId"    => $email->getId(),
                'isCopy'     => $isCopy,
                'recipients' => $recipients,
                "response" => [
                    "code"          => $response->getCode(),
                    "message"       => $response->getMessage(),
                    "invalidFields" => $response->getInvalidFields(),
                ]
            ]);

            return false;
        }

        if( empty($email->getToolName()) || !$hasExternalId ){
            $this->emailController->save($email);
        }

        return true;
    }

    /**
     * Will go over the user uploaded files to check if any of them is being referenced in sent E-Mail,
     * and if it is then such entry will get updated in the database
     *
     * It would sound sane to update the {@see UploadedFile} when inserting the E-Mail into database, not on-send,
     * but there is no centralized logic for "insert into db". This places is kinda fine because the {@see UploadedFile},
     * won't get removed if are referenced somewhere:
     * - {@see UploadedFileRepository::isReferencedInAnyEmail()}
     */
    private function updateUsedUploadedFiles(Email $email): void
    {
        if (empty($email->getSender())) {
            return;
        }

        $uploadedFiles             = $this->uploadedFileRepository->findDeletableForUser($email->getSender());
        $noLongerDeletableEntities = [];
        foreach ($uploadedFiles as $uploadedFile) {
            if (str_contains($email->getBody(), $uploadedFile->getLocalFileName())) {
                $uploadedFile->setDeletable(false);
                $noLongerDeletableEntities[] = $uploadedFile;
            }
        }

        $uploadedFileIds = [];
        foreach ($noLongerDeletableEntities as $notDeletableEntity) {
            $this->entityManager->persist($notDeletableEntity);
            $uploadedFileIds[] = $notDeletableEntity->getId();
        }

        $this->logger->info("Marking given uploaded file entities as not deletable", [
            "ids" => $uploadedFileIds,
        ]);

        try {
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->logger->emergency("Failed marking uploaded entities as not-deletable", [
                "info" => "EMERGENCY, because if cleanup cron will remove the files then they wont render in sent E-Mails",
                "todo" => "Need to updated the database entries manually",
                "ids"  => $uploadedFileIds,
            ]);
            throw $e;
        }
    }

    /**
     * Will return array of attachments data
     *
     * @param Email $email
     * @return array
     */
    private function buildAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $fullPath                                = $attachment->getPath() . $attachment->getFileName();
            $fileContent                             = base64_encode(file_get_contents($fullPath));
            $attachments[$attachment->getFileName()] = $fileContent;
        }

        return $attachments;
    }

    /**
     * Will anonymize the {@see Email}
     *
     * @param Email $email
     */
    public function anonymize(Email $email): void
    {
        $email->setBody("");
        $email->setSubject("");
        $email->setError(null);
        $email->setTemplate(null);
        $email->setAnonymized(true);
    }

}