<?php

namespace App\Controller\Interfaces;

use App\Entity\Interfaces\EntityInterface;

/**
 * Ensures that controller provides anonymization logic
 */
interface AnonymizationControllerInterface
{
    /**
     * Handles anonymization of provided entity
     *
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function anonymize(EntityInterface $entity): EntityInterface;
}