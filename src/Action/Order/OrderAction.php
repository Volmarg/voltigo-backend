<?php

namespace App\Action\Order;

use App\DTO\Order\OrderDataDto;
use App\Entity\Ecommerce\Order;
use App\Response\Order\GetOrdersDataResponse;
use App\Service\Order\OrderService;
use App\Service\Security\JwtAuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderAction extends AbstractController
{
    /**
     * @param JwtAuthenticationService $jwtAuthenticationService
     * @param OrderService             $orderService
     */
    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly OrderService             $orderService,
    ){}

    #[Route("/orders/get-all", name: "orders.get_all", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getAllOrdersData(): JsonResponse
    {
        $user          = $this->jwtAuthenticationService->getUserFromRequest();
        $orderDataDtos = array_map(
            fn(Order $order) => $this->orderService->buildOrderDataDto($order),
            $user->getOrders()->filter(fn(Order $order) => !$order->isPreparedState())->toArray()
        );

        // sort by id DESC
        usort($orderDataDtos, fn (OrderDataDto $current , OrderDataDto $next) =>  strcmp($next->getId(), $current->getId()));

        $response = GetOrdersDataResponse::buildOkResponse();
        $response->setOrdersData($orderDataDtos);

        return $response->toJsonResponse();
    }

}