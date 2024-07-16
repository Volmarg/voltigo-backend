<?php

namespace App\Action\System;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Response\Api\System\IsSystemDisabledResponse;
use App\Response\System\State\OffersHandlerState;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\System\State\SystemStateService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * System state related endpoints
 */
class SystemStateAction
{
    public const ROUTE_NAME_IS_SYSTEM_DISABLED = "system.state.is_system_disabled";

    public function __construct(
        private readonly JobSearchService    $jobSearchService,
        private readonly SystemStateService  $systemStateService,
        private readonly TranslatorInterface $translator
    ){}

    /**
     * Get base state of the offer handler:
     * - is it running at all,
     * - how many extractions are running,
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     */
    #[Route("/system/state/get-offers-handler-state", name: "system.state.get-offers-handler-state")]
    public function getOffersHandlerState(): JsonResponse
    {
        $response    = OffersHandlerState::buildOkResponse();
        $isReachable = $this->jobSearchService->pingService();

        if ($isReachable) {
            $isQuotaReached = $this->systemStateService->isOffersHandlerQuotaReached();
            $response->setQuotaReached($isQuotaReached);
        }

        $response->setReachable($isReachable);

        return $response->toJsonResponse();
    }

    /**
     * Check if system is disabled and send the response with that information
     *
     * @throws Exception
     */
    #[JwtAuthenticationDisabledAttribute]
    #[Route("/system/state/is-system-disabled", name: self::ROUTE_NAME_IS_SYSTEM_DISABLED, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function isSystemDisabled(): JsonResponse
    {
        $response = new IsSystemDisabledResponse();
        $response->setMessage($this->translator->trans('state.disabled.downForMaintenance'));
        $response->setDisabled($this->systemStateService->isSystemDisabled());
        $response->setDisabledInt((int)$this->systemStateService->isSystemDisabled());

        return $response->toJsonResponse();
    }

}