<?php

namespace App\Service\Email;

use App\Controller\Core\Env;
use App\Entity\Email\Email;
use App\Exception\LogicFlow\Email\Modifier\EmailModifierException;
use App\Service\Email\Modifiers\EmailModifierInterface;
use App\Service\Email\Modifiers\ProjectFooterModifier;
use Psr\Log\LoggerInterface;

/**
 * Handles extra modifications to the sent E-Mails
 */
class EmailModifierService
{

    /**
     * @var bool $blockingUrl
     */
    private bool $blockingUrl = true;

    /**
     * @return bool
     */
    public function isBlockingUrl(): bool
    {
        return $this->blockingUrl;
    }

    /**
     * @param bool $blockingUrl
     */
    public function setBlockingUrl(bool $blockingUrl): void
    {
        $this->blockingUrl = $blockingUrl;
    }

    /**
     * @param EmailModifierInterface[] $modifiers
     */
    public function __construct(
        private readonly array           $modifiers,
        private readonly LoggerInterface $logger
    ){}

    /**
     * Will apply set of changes to the E-Mail body content
     *
     * @param Email $email
     *
     * @return string
     *
     * @throws EmailModifierException
     */
    public function applyChanges(Email $email): string
    {
        $bodyContent = $email->getBody();
        if (!Env::isEmailTemplateModifier()) {
            return $bodyContent;
        }

        if (!$this->canModify($email)) {
            $message = "E-Mail ({$email->getId()}) modifier logic cannot be applied. Check log entry for more details.";
            throw new EmailModifierException($message);
        }

        $modifiedContent = $bodyContent;
        foreach ($this->modifiers as $modifier) {
            $modifier->setRecipient($email->getFirstRecipient());
            $modifier->setFromAddress($email->getSender()?->getEmail());

            switch ($modifier::class) {
                case ProjectFooterModifier::class:
                    $modifier->setBlockingUrl($this->isBlockingUrl());
                    break;
            }

            $modifiedContent = $modifier->modify($modifiedContent);
        }

        return $modifiedContent;
    }

    /**
     * Check if the email can be modified or not
     *
     * @param Email $email
     *
     * @return bool
     */
    private function canModify(Email $email): bool
    {
        if ($email->countRecipients() > 1) {
            $this->logger->critical("Cannot modify E-mail (Id: {$email->getId()}) with multiple recipients.");
            return false;
        }

        if (0 === $email->countRecipients()) {
            $this->logger->critical("Cannot modify E-mail (Id: {$email->getId()}) without any recipients.");
            return false;
        }

        return true;
    }
}