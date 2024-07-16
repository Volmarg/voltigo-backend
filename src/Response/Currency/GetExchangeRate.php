<?php

namespace App\Response\Currency;

use App\Response\Base\BaseResponse;

/**
 * Response delivering the exchange rate of 2 currencies
 */
class GetExchangeRate extends BaseResponse
{
    private ?float $exchangeRate = null;

    /**
     * @return float|null
     */
    public function getExchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    /**
     * @param float|null $exchangeRate
     */
    public function setExchangeRate(?float $exchangeRate): void
    {
        $this->exchangeRate = $exchangeRate;
    }

}