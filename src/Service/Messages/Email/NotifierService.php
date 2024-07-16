<?php

namespace App\Service\Messages\Email;

use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Env;
use Exception;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Handles sending notifications mails
 */
class NotifierService
{
    const MAIL_CHANNEL_NAME      = "email";
    const LOCALHOST_SENDMAIL_DSN = "sendmail://default";

    const DSN_TYPE_LOCALHOST_SENDMAIL = "LOCALHOST_SENDMAIL";
    const DNS_TYPE_ENV_BASED          = "TYPE_ENV_BASED";

    const ALL_SUPPORTED_TYPES = [
        self::DSN_TYPE_LOCALHOST_SENDMAIL,
        self::DNS_TYPE_ENV_BASED,
    ];

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    public function __construct(EventDispatcherInterface $eventDispatcher, ConfigLoader $configLoader)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->configLoader    = $configLoader;
    }

    /**
     * Will send single notification message
     *
     * @param string $notifierType
     * @param array $toMails
     * @param string $messageBody
     * @param string $messageSubject
     * @throws Exception
     */
    public function sendNotificationMessage(string $notifierType, array $toMails, string $messageBody, string $messageSubject): void
    {
        $notifier = $this->getNotifierForType($notifierType);

        $notification = new Notification();
        $notification->subject($messageSubject);
        $notification->content($messageBody);
        $notification->channels([self::MAIL_CHANNEL_NAME]);

        foreach($toMails as $singleEmailString){
            $notificationRecipient = new Recipient($singleEmailString);
            $notifier->send($notification, $notificationRecipient);
        }
    }

    /**
     * Will return preconfigured notifier based on provided type
     * - {@see NotifierService::DSN_TYPE_LOCALHOST_SENDMAIL}
     * - {@see NotifierService::DNS_TYPE_ENV_BASED}
     *
     * @param string $type
     * @return Notifier
     * @throws Exception
     */
    private function getNotifierForType(string $type): Notifier
    {
        switch($type)
        {
            case self::DNS_TYPE_ENV_BASED:
            {
                return $this->getNotifierForSendingMailNotificationsByEnvDsn();
            }

            case self::DSN_TYPE_LOCALHOST_SENDMAIL:
            {
                return $this->getNotifierForSendingMailNotificationsByUsingLocalSendmail();
            }

            default:
                throw new Exception("Unhandled notifier type: {$type}");
        }
    }

    /**
     * Will return notifier instance for sending mail messages by using local sendmail package
     *
     * @return Notifier
     */
    private function getNotifierForSendingMailNotificationsByUsingLocalSendmail(): Notifier
    {
        $notifier = $this->buildNotifierForDsnString(self::LOCALHOST_SENDMAIL_DSN);
        return $notifier;
    }

    /**
     * Will return notifier instance for sending mail messages by using local sendmail package
     *
     * @return Notifier
     */
    public function getNotifierForSendingMailNotificationsByEnvDsn(): Notifier
    {
        $notifier = $this->buildNotifierForDsnString(Env::getMailerDsn());
        return $notifier;
    }

    /**
     * Will build the mailer (MAILER_DSN) connection string used internally by symfony
     *
     * @param string $dsnString
     * @return Notifier
     */
    private function buildNotifierForDsnString(string $dsnString): Notifier
    {
        $fromMail = $this->configLoader->getConfigLoaderProject()->getFromMail();

        $stopWatch   = new Stopwatch(true);
        $dispatcher  = new TraceableEventDispatcher($this->eventDispatcher, $stopWatch);
        $transport   = Transport::fromDsn($dsnString, $dispatcher);
        $mailChannel = new EmailChannel($transport, null, $fromMail);
        $notifier    = new Notifier([self::MAIL_CHANNEL_NAME => $mailChannel]);

        return $notifier;
    }

}