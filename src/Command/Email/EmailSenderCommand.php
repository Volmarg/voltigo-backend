<?php

namespace App\Command\Email;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Services;
use App\DTO\Validation\ValidationResultDTO;
use App\Entity\Email\Email;
use App\Repository\Email\EmailRepository;
use App\Service\Email\EmailSenderService;
use App\Service\Serialization\ObjectSerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

class EmailSenderCommand extends AbstractCommand
{
    const COMMAND_NAME = "email:send-emails";

    private const PARAM_NAME_SINGLE_ID = "single-id";

    /**
     * @var string|null $emailId
     */
    private ?string $emailId = null;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->emailId = $input->getOption(self::PARAM_NAME_SINGLE_ID) ?: null;
    }

    /**
     * {@inheritDoc}
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("This command will send emails to the project which then passes them forward to proper recipients");
        $this->addOption(self::PARAM_NAME_SINGLE_ID, null, InputOption::VALUE_REQUIRED, "Id of E-Mail that should be sent (only one E-Mail will be sent)");
    }

    public function __construct(
        protected ConfigLoader                   $configLoader,
        private Services                         $services,
        private readonly ObjectSerializerService $objectSerializerService,
        private readonly EmailRepository         $emailRepository,
        private readonly EmailSenderService      $emailSenderService,
        private readonly KernelInterface         $kernel,
        private readonly EntityManagerInterface  $entityManager
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * It's known that this command is slow. Nothing more can be done about it. The bottleneck is:
     * - persist + flush on each sent E-Mail
     *
     * It's made this way to prevent having a case where bulk of E-Mails get sent, something crashes before or on flush,
     * and E-Mails would remain in "PENDING", it's safer this way.
     *
     * {@inheritDoc}
     * @throws GuzzleException
     */
    protected function executeLogic(): int
    {
        $handledEmails = $this->findEmails();
        if (empty($handledEmails)) {
            $this->io->info("There are no E-mails to insert");

            return self::SUCCESS;
        }

        $emailsCount = count($handledEmails);
        $this->io->info("Got {$emailsCount} E-mail/s to send");
        foreach ($handledEmails as $email) {
            try{
                $canSend = $this->canEmailBeSent($email);
                if (!$canSend) {
                    continue;
                }

                $allRecipients = $email->getRecipients();
                if ($email->isSendCopyToSender() && $email->isSenderSet()) {
                    $allRecipients[] = $email->getSender()->getEmail();
                }

                $validationResults = $this->emailSenderService->validateRecipients($allRecipients, $email);
                if (!empty($validationResults)) {
                    $jsonResults = array_map(
                        fn(ValidationResultDTO $result) => $this->objectSerializerService->toArray($result),
                        $validationResults,
                    );

                    $this->services->getLoggerService()->critical("There were some issues with sending some E-Mails", [
                        "validationResults" => $jsonResults,
                    ]);
                    continue;
                }

                $isSent = $this->emailSenderService->handleEmailSending($email, $email->getRecipients());
                if (!$isSent) {
                    $this->logger->error("Could not send email ({$email->getId()}) to main recipients");
                    continue;
                }

                if ($email->isSendCopyToSender() && $email->isSenderSet()) {
                    $isSent = $this->emailSenderService->handleEmailSending($email, [$email->getSender()->getEmail()], true);
                    if (!$isSent) {
                        $this->logger->error("Could not send copy of email ({$email->getId()}) to sender");
                        continue;
                    }
                }

                try {
                    $this->emailSenderService->anonymize($email);
                    $this->entityManager->persist($email);
                } catch (Exception|TypeError $e) {
                    $this->logger->critical("Could not anonymize E-Mail with id: {$email->getId()}");
                    throw $e;
                }

            }catch(Exception | TypeError $e){
                $this->services->getLoggerService()->logException($e, ["Could not send E-Mail with id: {$email->getId()}"]);
                continue;
            }

        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    /**
     * Check if given E-Mail can be sent or not
     *
     * @param Email $email
     *
     * @return bool
     */
    private function canEmailBeSent(Email $email): bool
    {
        if(
                is_null($email->getExternalId())
            &&  $email->isTransferred()
        ){
            $message = "
                    Something is wrong with one of the emails found for sending.
                    Email id: {$email->getId()},
                    - has no external id,
                    - is marked as sent,
                    
                    This E-Mail will be skipped from re/sending
                ";
            $this->logger->critical($message);
            return false;
        }

        if ($email->getSender()?->isDeleted()) {
            $this->logger->critical("Tried to send E-Mail to deleted user. {$email->getId()}. This is not allowed.");
            return false;
        }

        return true;
    }

    /**
     * Returns E-Mails that will be sent
     * 
     * @return Email[]
     */
    private function findEmails(): array
    {
        if (!empty($this->emailId)) {
            $handledEmails = $this->emailRepository->findByIds([$this->emailId]);
        } else {
            $handledEmails = $this->emailRepository->getAllEmailsForSending();
        }

        return $handledEmails;
    }
}
