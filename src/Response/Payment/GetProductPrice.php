<?php

namespace App\Response\Payment;

use App\Response\Base\BaseResponse;

/**
 * Response delivering the prices of the product
 */
class GetProductPrice extends BaseResponse
{
    private float $unitPriceWithoutTax;
    private float $unitPriceWithTax;

    /**
     * @return float
     */
    public function getUnitPriceWithoutTax(): float
    {
        return $this->unitPriceWithoutTax;
    }

    /**
     * @param float $unitPriceWithoutTax
     */
    public function setUnitPriceWithoutTax(float $unitPriceWithoutTax): void
    {
        $this->unitPriceWithoutTax = $unitPriceWithoutTax;
    }

    /**
     * @return float
     */
    public function getUnitPriceWithTax(): float
    {
        return $this->unitPriceWithTax;
    }

    /**
     * @param float $unitPriceWithTax
     */
    public function setUnitPriceWithTax(float $unitPriceWithTax): void
    {
        $this->unitPriceWithTax = $unitPriceWithTax;
    }

}