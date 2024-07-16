<?php

namespace App\Command\Test\Messages;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Env;
use App\Service\Messages\Email\NotifierService;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Helper command to test sending notification mails
 *
 * Class ValidateCsrfTokenCommand
 * @package App\Command\Validation
 */
class TestSendingNotificationMessageCommand extends AbstractCommand
{
    const COMMAND_NAME = "test:send-notification-message";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will send single notification email to env defined recipients");
    }

    /**
     * @var NotifierService $notifierService
     */
    private NotifierService $notifierService;

    public function __construct(
        NotifierService                  $notifierService,
        ConfigLoader                     $configLoader,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->notifierService = $notifierService;
    }

    /**
     * Execute the command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        $dnsType = $this->io->choice("Which dsn method do You want to use?", NotifierService::ALL_SUPPORTED_TYPES);

        $body    = "Message body set in: " . __CLASS__;
        $subject = "Message subject set in: " . __CLASS__;

        try{
            $recipients = explode(",", Env::getAdminEmail());

            $this->notifierService->sendNotificationMessage($dnsType, $recipients, $body, $subject);
            $this->io->success("Message has been sent");
        }catch(\Exception | \TypeError $e){
            $this->io->error("Message could not been sent");
            $this->io->info("Exception message: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}