<?php

namespace App\DTO\Internal\Upload;

use App\Exception\Security\SafetyException;
use App\Service\File\FileUploadValidatorInterface;

/**
 * Upload configuration defines how the upload work on front and what kind of validations will be performed
 * both on front and back
 */
class UploadConfigurationDTO
{
    /**
     * {@link https://lian-yue.github.io/vue-upload-component/#/documents#options-props-size}
     */
    public const FILE_SIZE_NO_LIMIT = 0;

    private readonly array $fileNameValidationRegexps;

    public function __construct(
        private readonly string $identifier,
        private readonly float  $maxFileSizeMb,
        private readonly bool   $multiUpload,
        private readonly bool   $allowNaming,
        private readonly string $source,
        private readonly array  $allowedExtensions = [],
        private readonly array  $allowedMimeTypes = [],
    ){
        $this->fileNameValidationRegexps = [
            FileUploadValidatorInterface::DISALLOWED_CHARACTERS_REGEXP,
            FileUploadValidatorInterface::MULTI_EXTENSION_REGEXP,
        ];
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return float
     */
    public function getMaxFileSizeMb(): float
    {
        return $this->maxFileSizeMb;
    }

    /**
     * @return bool
     */
    public function isMultiUpload(): bool
    {
        return $this->multiUpload;
    }

    /**
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @return array
     */
    public function getFileNameValidationRegexps(): array
    {
        return $this->fileNameValidationRegexps;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function isAllowNaming(): bool
    {
        return $this->allowNaming;
    }

    /**
     * Check some basic dto values,
     * keep in mind that some things are left for self validation, like for example:
     * - allowing "pdf" mime,
     * - denying "pdf" extension,
     *
     * which will result in being able to pick the pdf from upload window but then it's removal as extension is blocked
     *
     * @throws SafetyException
     */
    public function validateSelf(): void
    {
        if(
                empty($this->getAllowedExtensions())
            &&  empty($this->getAllowedMimeTypes())
        ){
            throw new SafetyException("Letting user upload ANY type of file is strictly forbidden!");
        }
    }

}