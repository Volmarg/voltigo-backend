<?php

namespace App\Response\PointShop;

use App\Entity\Ecommerce\User\UserPointHistory;
use App\Response\Base\BaseResponse;

/**
 * Response delivering {@see UserPointHistory}
 */
class GetFullPointShopHistoryResponse extends BaseResponse
{
    private array $historyEntries;

    /**
     * @return array
     */
    public function getHistoryEntries(): array
    {
        return $this->historyEntries;
    }

    /**
     * @param array $historyEntries
     */
    public function setHistoryEntries(array $historyEntries): void
    {
        $this->historyEntries = $historyEntries;
    }

}