<?php

namespace App\Action\Product;

use App\DTO\Product\PointProductDataDto;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Repository\Ecommerce\Product\PointProductRepository;
use App\Response\Product\GetAllPointProductDataResponse;
use App\Service\Payment\PriceCalculationService;
use App\Service\Points\UserPointsLimiterService;
use App\Service\Security\JwtAuthenticationService;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductAction extends AbstractController
{
    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly PointProductRepository  $productRepository,
        private readonly PriceCalculationService $priceCalculationService
    ){}

    /**
     * Returns the serialized {@see PointProductDataDto} which is filled with data from {@see PointProduct}
     *
     * @throws FinancesHubBridgeException
     *
     * @throws GuzzleException
     */
    #[Route("/product/get-all/point", name: "product.get_all.point", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getAllPointProducts(): JsonResponse
    {
        $user             = $this->jwtAuthenticationService->getUserFromRequest();
        $pointsIncPending = $user->getPointsAmountWithPendingOnes();
        $products         = $this->productRepository->findAllAccessible();
        $blockedIds       = [];

        foreach ($products as $product) {
            // skip the products which would result in user having more points than max allowed.
            if ($pointsIncPending + $product->getAmount() > UserPointsLimiterService::MAX_POINTS_PER_USER) {
                $blockedIds[] = $product->getId();
            }
        }

        $dtos = [];
        foreach ($products as $product) {
            $dto = new PointProductDataDto();
            $dto->setCurrencyCode($product->getBaseCurrencyCode());
            $dto->setName($product->getName());
            $dto->setDescription($product->getDescription());
            $dto->setPointsAmount($product->getAmount());
            $dto->setId($product->getId());

            $priceWithTax = $this->priceCalculationService->increaseByTax($product->getPrice());
            $dto->setPriceWithoutTax($product->getPrice());
            $dto->setPriceWithTax($priceWithTax);

            $dtos[] = $dto;
        }

        $response = GetAllPointProductDataResponse::buildOkResponse();
        $response->setProductsData($dtos);
        $response->setBlockedProductIds($blockedIds);

        return $response->toJsonResponse();
    }
}