<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity can be/is anonymized
 */
trait AnonymizableTrait
{
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $anonymized = false;

    /**
     * @return bool
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }

    /**
     * @param bool $anonymized
     */
    public function setAnonymized(bool $anonymized): void
    {
        $this->anonymized = $anonymized;
    }

}