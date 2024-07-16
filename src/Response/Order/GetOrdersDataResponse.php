<?php

namespace App\Response\Order;

use App\DTO\Order\OrderDataDto;
use App\Response\Base\BaseResponse;

/**
 * Response delivering the orders that user made
 */
class GetOrdersDataResponse extends BaseResponse
{
    /**
     * @var OrderDataDto[]
     */
    private array $ordersData;

    /**
     * @return array
     */
    public function getOrdersData(): array
    {
        return $this->ordersData;
    }

    /**
     * @param array $ordersData
     */
    public function setOrdersData(array $ordersData): void
    {
        $this->ordersData = $ordersData;
    }

}