<?php


namespace App\Exception;


use Exception;
use Throwable;
use TypeError;

/**
 * Base exception for all project based exceptions
 *
 * Class BaseException
 * @package App\Exception
 */
class BaseException extends Exception
{

    /**
     * Adding final to mute phpstan about possible issues with return / new static.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    final public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Contains (at least should) the original message of caught exception
     *
     * @var string $originalMessage
     */
    private string $originalMessage = "";

    /**
     * Contains (at least should) the original code of caught exception
     *
     * @var int|null $originalCode
     */
    private ?int $originalCode = null;

    /**
     * Contains (at least should) the original trace of caught exception
     *
     * @var array $originalTrace
     */
    private array $originalTrace = [];

    /**
     * Return exception instance, set the message and prefill original exception fields from other Exception
     *
     * @param string $message
     * @param Exception|TypeError $originalException
     * @return static
     */
    public static function buildFromOriginalFieldsFromException(string $message, Exception | TypeError $originalException): static
    {
        $exception = new static();
        $exception->message         = $message;
        $exception->originalTrace   = $originalException->getTrace();
        $exception->originalMessage = $originalException->getMessage();
        $exception->originalCode    = $originalException->getCode();

        return $exception;
    }
}