<?php

namespace App\Exception\Security;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Indicates that given file is infected / malicious etc.
 */
class MaliciousFileException extends Exception
{
    public function __construct(string $path)
    {
        $message = "Provided file is malicious: {$path}";
        parent::__construct($message, Response::HTTP_BAD_REQUEST);
    }
}