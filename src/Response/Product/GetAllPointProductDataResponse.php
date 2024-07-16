<?php

namespace App\Response\Product;

use App\DTO\Product\PointProductDataDto;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Response\Base\BaseResponse;

/**
 * Response delivering the available {@see PointProduct} data
 */
class GetAllPointProductDataResponse extends BaseResponse
{
    /**
     * @var PointProductDataDto[]
     */
    private array $productsData = [];

    /**
     * @var array $blockedProductIds
     */
    private array $blockedProductIds = [];

    /**
     * @return array
     */
    public function getProductsData(): array
    {
        return $this->productsData;
    }

    /**
     * @param array $productsData
     */
    public function setProductsData(array $productsData): void
    {
        $this->productsData = $productsData;
    }

    /**
     * @return array
     */
    public function getBlockedProductIds(): array
    {
        return $this->blockedProductIds;
    }

    /**
     * @param array $blockedProductIds
     */
    public function setBlockedProductIds(array $blockedProductIds): void
    {
        $this->blockedProductIds = $blockedProductIds;
    }

}