<?php

namespace App\Command;

class DebugCommand extends AbstractCommand
{
    const COMMAND_NAME = "debug";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Command for debugging random things - can and will keep changing over time");
    }

    /**
     * Execute the command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        return self::SUCCESS;
    }

}