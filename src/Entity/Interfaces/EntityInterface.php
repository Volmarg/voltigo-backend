<?php

namespace App\Entity\Interfaces;

/**
 * Interfaces for marking object as an entity - way much easier than all the doctrine checks later on
 */
interface EntityInterface
{
    /**
     * Return entity id or null if not yet persisted
     *
     * @return int|null
     */
    public function getId(): ?int;
}