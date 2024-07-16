<?php

namespace App\Exception\Payment\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use Exception;

/**
 * Related to {@see PointShopProduct} - indicates that user has not enough points to get the product
 */
class NotEnoughPointsException extends Exception
{

}