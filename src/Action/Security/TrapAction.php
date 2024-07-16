<?php

namespace App\Action\Security;

use App\Controller\Core\Env;
use App\Response\Base\BaseResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * That's special service for trapping in abused calls, by for example sleeping the request for 9999 seconds etc.
 */
class TrapAction
{
    public const ROUTE_NAME_ENDLESS_DREAM = "trap.endless_dream";

    /**
     * Returns the amount of seconds for which the script will be sleeping (trapping user / caller)
     * @return int
     */
    private function getSleepTime(): int
    {
        if (Env::isDev()) {
            return 5;
        }

        return 9999999999999;
    }

    /**
     * Trap the caller in the endless sleep
     *
     * @return JsonResponse
     */
    #[Route("/trap/endless-dream/{id}", name: self::ROUTE_NAME_ENDLESS_DREAM)]
    public function endlessDream(): JsonResponse
    {
        ini_set('max_execution_time', $this->getSleepTime());
        sleep($this->getSleepTime());

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }
}