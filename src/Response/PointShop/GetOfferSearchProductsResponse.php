<?php

namespace App\Response\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use App\Response\Base\BaseResponse;

/**
 * Response delivering point shop products that can be used in job offers search (offers search limit)
 */
class GetOfferSearchProductsResponse extends BaseResponse
{
    private array $pointShopProducts;

    /**
     * @return Array<PointShopProduct>
     */
    public function getPointShopProducts(): array
    {
        return $this->pointShopProducts;
    }

    /**
     * @param Array<PointShopProduct> $pointShopProducts
     */
    public function setPointShopProducts(array $pointShopProducts): void
    {
        $this->pointShopProducts = $pointShopProducts;
    }

}