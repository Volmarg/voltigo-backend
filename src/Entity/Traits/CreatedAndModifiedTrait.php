<?php

namespace App\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * This entity supports created / modified time stamps
 */
trait CreatedAndModifiedTrait
{
    use CreatedTrait;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    protected ?DateTime $modified;

    public function getModified(): ?\DateTime
    {
        return $this->modified;
    }

    public function setModified(?\DateTime $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Will init DT instances
     */
    public function initCreatedAndModified(): void {
        $this->modified = new DateTime();
        $this->initCreated();
    }
}