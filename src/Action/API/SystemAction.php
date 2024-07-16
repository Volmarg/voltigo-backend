<?php

namespace App\Action\API;

use App\Response\Api\System\IsSystemDisabledResponse;
use App\Service\System\State\SystemStateService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handling of system state logic toward/from external systems
 */
#[Route("/api/system", name: "api.system.")]
class SystemAction extends AbstractController
{

    public function __construct(
        private readonly SystemStateService $systemStateService
    ) {
    }

    /**
     * Check if system is disabled and send the response with that information
     *
     * @throws Exception
     */
    #[Route("/is-system-disabled", name: "is_system_disabled", methods: Request::METHOD_GET)]
    public function isSystemDisabled(): JsonResponse
    {
        $response = new IsSystemDisabledResponse();
        $response->setDisabled($this->systemStateService->isSystemDisabled());
        $response->setDisabledInt((int)$this->systemStateService->isSystemDisabled());

        return $response->toJsonResponse();
    }

    /**
     * Check if system is disabled and send the response with that information.
     * Similar to {@see self::isSystemDisabled()}, but that one checks if system
     * is in general disabled, while current method checks if it's disabled
     * as planned, or for some reason system is disabled due to some bug etc.
     *
     * @throws Exception
     */
    #[Route("/is-system-unexpectedly-disabled", name: "is_system_unexpectedly_disabled", methods: Request::METHOD_GET)]
    public function isSystemUnexpectedlyDisabled(): JsonResponse
    {
        $isDisabled = $this->systemStateService->isSystemDisabledViaFile();

        $response = new IsSystemDisabledResponse();
        $response->setDisabled($isDisabled);
        $response->setDisabledInt((int)$isDisabled);

        return $response->toJsonResponse();
    }

}