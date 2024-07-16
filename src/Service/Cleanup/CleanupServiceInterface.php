<?php

namespace App\Service\Cleanup;

/**
 * Common interface for defining the cleanup handler
 */
interface CleanupServiceInterface
{
    /**
     * Handles removal of the data
     */
    public function cleanUp(): int;
}