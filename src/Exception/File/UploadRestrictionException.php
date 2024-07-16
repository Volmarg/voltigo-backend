<?php

namespace App\Exception\File;

use App\DTO\Validation\ValidationResultDTO;
use Exception;

/**
 * Indicates that there is an upload restriction breach
 */
class UploadRestrictionException extends Exception
{
    private ?ValidationResultDTO $validationResult = null;

    /**
     * @return ValidationResultDTO|null
     */
    public function getValidationResult(): ?ValidationResultDTO
    {
        return $this->validationResult;
    }

    /**
     * @param ValidationResultDTO|null $validationResult
     */
    public function setValidationResult(?ValidationResultDTO $validationResult): void
    {
        $this->validationResult = $validationResult;
    }

}