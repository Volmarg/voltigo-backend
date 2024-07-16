<?php

namespace App\Action\Debug\PointShop;

use App\Enum\Points\Shop\JobOfferSearchProductIdentifierEnum;
use App\Response\Api\BaseApiResponse;
use App\Service\PointShop\PointShopProductPaymentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/debug", name: "debug_", methods: [Request::METHOD_OPTIONS])]
class PointShopDebugAction
{
    public function __construct(
        private readonly PointShopProductPaymentService $pointShopProductPaymentService
    ){}

    #[Route("/point-shop/buy", name: "point_shop.buy", methods: Request::METHOD_GET)]
    public function buyProduct(): JsonResponse
    {
        $this->pointShopProductPaymentService->buy(JobOfferSearchProductIdentifierEnum::JOB_SEARCH_TAG_NO_LIMIT->name, 1);
        return (new BaseApiResponse())->toJsonResponse();
    }
}