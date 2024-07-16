<?php

namespace App\Listener\Kernel\Request;

use App\Action\Security\TrapAction;
use App\Service\Routing\UrlMatcherService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TrappedRequestListener implements EventSubscriberInterface
{

    /**
     * These routes are used to trap users / callers on them
     */
    private const TRAPPED_ROUTE_NAMES = [
        TrapAction::ROUTE_NAME_ENDLESS_DREAM,
    ];

    public function __construct(
        private readonly UrlMatcherService $urlMatcherService
    ){}

    /**
     * Deny processing the request further if user is on trapped page
     * - this helps to avoid endless loops etc.
     */
    public function onRequest(RequestEvent $requestEvent)
    {
        $calledRoute = $this->urlMatcherService->getRouteForCalledUri($requestEvent->getRequest()->getRequestUri());

        if (in_array($calledRoute, self::TRAPPED_ROUTE_NAMES)) {
            $requestEvent->stopPropagation();
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                "onRequest", -47
            ],
        ];
    }

}