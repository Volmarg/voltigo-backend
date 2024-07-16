<?php

namespace App\Response\Currency;

use App\Response\Base\BaseResponse;

/**
 * Response delivering all the currency codes
 */
class GetAllCurrencyCodesResponse extends BaseResponse
{
    private array $currencyCodes = [];

    /**
     * @return array
     */
    public function getCurrencyCodes(): array
    {
        return $this->currencyCodes;
    }

    /**
     * @param array $currencyCodes
     */
    public function setCurrencyCodes(array $currencyCodes): void
    {
        $this->currencyCodes = $currencyCodes;
    }

}