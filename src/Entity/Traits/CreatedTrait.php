<?php

namespace App\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreatedTrait
{

    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $created;

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function initCreated(): void
    {
        $this->created  = new DateTime();
    }

}