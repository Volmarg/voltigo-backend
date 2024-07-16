<?php

namespace App\Service\Email\Modifiers;

/**
 * Describes the E-Mail modifier logic
 */
interface EmailModifierInterface
{
    /**
     * Will apply some set of changes to the E-Mail body
     *
     * @param string $body
     *
     * @return string
     */
    public function modify(string $body): string;

    /**
     * @return string
     */
    public function getRecipient(): string;

    /**
     * @param string $recipient
     */
    public function setRecipient(string $recipient): void;

    /**
     * @param string|null $fromAddress
     */
    public function setFromAddress(?string $fromAddress): void;

    /**
     * @return string|null
     */
    public function getFromAddress(): ?string;
}