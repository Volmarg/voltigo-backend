<?php

namespace App\Action\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use App\Enum\Points\Shop\JobOfferSearchProductIdentifierEnum;
use App\Enum\Points\Shop\ProductIdentifierEnum;
use App\Exception\NotFoundException;
use App\Repository\Ecommerce\PointShopProductRepository;
use App\Response\PointShop\GetOfferSearchProductsResponse;
use App\Response\PointShop\GetPointShopProductResponse;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This class purpose is to deliver the data about {@see PointShopProduct} which user can buy for internal points system
 */
#[Route("/point-shop/", name: "point_shop.", methods: Request::METHOD_OPTIONS)]
class PointShopProductProviderAction extends AbstractController
{

    public function __construct(
        private readonly PointShopProductRepository $pointShopProductRepository
    ) {
    }

    /**
     * Will return the data about the {@see PointShopProduct} which represents the email sending
     * it basically is the product for which user gets points billed when sending emails
     *
     * @return JsonResponse
     *
     * @throws NotFoundException
     */
    #[Route("email-sending", name: "email-sending", methods: [Request::METHOD_GET])]
    public function getEmailSendingProduct(): JsonResponse
    {
        $response   = GetPointShopProductResponse::buildOkResponse();
        $identifier = ProductIdentifierEnum::EMAIL_SENDING->name;
        $product    = $this->pointShopProductRepository->findByInternalIdentifier($identifier);
        $this->validateProduct($product, $identifier);

        $response->setPointShopProduct($product);

        return $response->toJsonResponse();
    }

    /**
     * Returns the products that can be used in job search results
     *
     * @return JsonResponse
     *
     * @throws NotFoundException
     */
    #[Route("job-offer-search/all", name: "job_offer_search_products", methods: [Request::METHOD_GET])]
    public function getOfferSearchProducts(): JsonResponse
    {
        $response = GetOfferSearchProductsResponse::buildOkResponse();
        $products = [];
        foreach (JobOfferSearchProductIdentifierEnum::cases() as $enum) {
            $product = $this->pointShopProductRepository->findByInternalIdentifier($enum->name);
            $this->validateProduct($product, $enum->name);
            $products[] = $product;
        }

        if (empty($products)) {
            throw new LogicException("There are no offers search products in the array!");
        }

        $response->setPointShopProducts($products);

        return $response->toJsonResponse();
    }

    /**
     * @throws NotFoundException
     */
    private function validateProduct(?PointShopProduct $product, string $identifier): void
    {
        if (empty($product)) {
            throw new NotFoundException("No point shop product exists for identifier: {$identifier}");
        }
    }

}