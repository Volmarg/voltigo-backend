<?php


namespace App\DTO\Validation;

use App\Service\Validation\ValidationService;
use Symfony\Component\Validator\Validation;

/**
 * This DTO should be used in any logic performing validation
 *
 * Class ValidationResultDTO
 * @package App\DTO\Internal
 */
class ValidationResultDTO
{

    /**
     * @var bool $success
     */
    private bool $success;

    /**
     * @var array $violationsWithMessages
     */
    private array $violationsWithMessages = [];

    /**
     * @var string|null $message
     */
    private ?string $message = null;

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * Will return grouped violations with messages
     * - {@see ValidationService::validateAndReturnArrayOfInvalidFieldsWithMessages()}
     *
     * @return array
     */
    public function getViolationsWithMessages(): array
    {
        return $this->violationsWithMessages;
    }

    /**
     * @param array $violationsWithMessages
     */
    public function setViolationsWithMessages(array $violationsWithMessages): void
    {
        $this->violationsWithMessages = $violationsWithMessages;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return ValidationResultDTO
     */
    public static function buildOkValidation(): ValidationResultDTO
    {
        $dto = new ValidationResultDTO();
        $dto->setSuccess(true);
        $dto->setMessage("OK");

        return $dto;
    }

    /**
     * @param string $message
     * @param array  $violationsWithMessages
     *
     * @return ValidationResultDTO
     */
    public static function buildInvalidValidation(string $message = "Validation failed", array $violationsWithMessages = []): ValidationResultDTO
    {
        $dto = new ValidationResultDTO();
        $dto->setSuccess(false);
        $dto->setMessage($message);
        $dto->setViolationsWithMessages($violationsWithMessages);

        return $dto;
    }
}