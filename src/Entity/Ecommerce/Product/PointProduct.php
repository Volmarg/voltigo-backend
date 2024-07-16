<?php

namespace App\Entity\Ecommerce\Product;

use App\Entity\Interfaces\EntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class PointProduct extends Product implements EntityInterface
{
    /**
     * @ORM\Column(type="integer")
     */
    private int $amount;

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}
