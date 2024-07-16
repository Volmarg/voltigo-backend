<?php

namespace App\Action\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/health", name: "api.health.")]
class HealthAction
{

    /**
     * Simple ping toward the api - check if project is reachable
     * @return JsonResponse
     */
    #[Route("/ping", name: "ping", methods: [Request::METHOD_GET])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(["msg" => "OK"]);
    }
}