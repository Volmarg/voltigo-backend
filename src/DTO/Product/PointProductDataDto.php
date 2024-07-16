<?php

namespace App\DTO\Product;

class PointProductDataDto extends ProductDataDto
{
    private int $pointsAmount;

    /**
     * @return int
     */
    public function getPointsAmount(): int
    {
        return $this->pointsAmount;
    }

    /**
     * @param int $pointsAmount
     */
    public function setPointsAmount(int $pointsAmount): void
    {
        $this->pointsAmount = $pointsAmount;
    }

}