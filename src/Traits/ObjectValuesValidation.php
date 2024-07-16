<?php

namespace App\Traits;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Handles validation of an object
 */
trait ObjectValuesValidation
{

    /**
     * @param ClassMetadata $metadata
     */
    public static function objectValuesValidator(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Callback('validateValues'));
    }

    /**
     * @param ExecutionContextInterface $context
     */
    abstract public function validateValues(ExecutionContextInterface $context);
}