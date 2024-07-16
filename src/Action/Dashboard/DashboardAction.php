<?php

namespace App\Action\Dashboard;

use App\Service\Dashboard\DashboardDataProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/dashboard", name: "dashboard.", methods: [Request::METHOD_OPTIONS])]
class DashboardAction extends AbstractController
{

    public function __construct(
        private readonly DashboardDataProviderService $dashboardDataProviderService
    ) {
    }

    /**
     * Returns data for dashboard.
     *
     * @return JsonResponse
     */
    #[Route("/get-data", name: "get_data", methods: [Request::METHOD_GET])]
    public function getDashboardData(): JsonResponse
    {
        $response = $this->dashboardDataProviderService->getResponse();
        return $response->toJsonResponse();
    }
}