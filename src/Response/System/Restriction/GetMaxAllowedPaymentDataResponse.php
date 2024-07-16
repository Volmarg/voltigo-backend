<?php

namespace App\Response\System\Restriction;

use App\Response\Base\BaseResponse;

class GetMaxAllowedPaymentDataResponse extends BaseResponse
{
    private string $currency;
    private int $unit;

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getUnit(): int
    {
        return $this->unit;
    }

    /**
     * @param int $unit
     */
    public function setUnit(int $unit): void
    {
        $this->unit = $unit;
    }

}