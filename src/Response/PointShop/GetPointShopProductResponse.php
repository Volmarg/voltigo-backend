<?php

namespace App\Response\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use App\Response\Base\BaseResponse;

/**
 * Response delivering PointShop product
 */
class GetPointShopProductResponse extends BaseResponse
{
    private PointShopProduct $pointShopProduct;

    /**
     * @return PointShopProduct
     */
    public function getPointShopProduct(): PointShopProduct
    {
        return $this->pointShopProduct;
    }

    /**
     * @param PointShopProduct $pointShopProduct
     */
    public function setPointShopProduct(PointShopProduct $pointShopProduct): void
    {
        $this->pointShopProduct = $pointShopProduct;
    }

}