<?php

namespace App\Action\Points;

use App\Entity\Ecommerce\Product\PointProduct;
use App\Service\Points\UserPointService;
use App\Service\Security\JwtAuthenticationService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains logic related to any kind of direct manipulation of user points
 * - no ordering process,
 * - no transactions handling etc.
 */
#[Route("/user-point", name: "user_point.")]
class UserPointAction extends AbstractController
{
    /**
     * @param UserPointService         $userPointService
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function __construct(
        private readonly UserPointService         $userPointService,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ) {

    }

    /**
     * Handles granting user points directly, without any payment, transaction confirmation etc.
     *
     * @param PointProduct $product
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("/grant/{id}", "grant", methods: [Request::METHOD_OPTIONS, Request::METHOD_GET])]
    public function grant(PointProduct $product): JsonResponse
    {
        $user     = $this->jwtAuthenticationService->getUserFromRequest();
        $response = $this->userPointService->grantPoints($user, $product->getAmount());

        return $response->toJsonResponse();
    }
}
