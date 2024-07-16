<?php

namespace App\Entity\Storage;

use DateTime;

/**
 * Marks given entity as storage related one
 */
interface StorageInterface
{
    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime;

    /**
     * @param DateTime $created
     */
    public function setCreated(DateTime $created): self;

}