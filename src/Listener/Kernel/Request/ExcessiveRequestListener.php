<?php

namespace App\Listener\Kernel\Request;

use App\Action\Security\SystemAction;
use App\Action\Security\TrapAction;
use App\Response\Ban\BanResponse;
use App\Security\Ddos\DdosIpMonitor;
use App\Security\Ddos\DdosUserMonitor;
use App\Service\Routing\UrlMatcherService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This listener job is to control possible excessive / abusive amount of calls made to the service
 *
 * Class RequestListener
 * @package App\Listener\Kernel\Request
 */
class ExcessiveRequestListener implements EventSubscriberInterface
{
    /**
     * These routes got to be excluded when user is logged in, as it will break front,
     * and might not let the further logic to force push the user from the system.
     */
    public const USER_CHECK_EXCLUDED_ROUTES = [
      SystemAction::ROUTE_NAME_SYSTEM_GET_CSRF_TOKEN,
    ];

    public function __construct(
        private readonly DdosIpMonitor           $ddosMonitor,
        private readonly DdosUserMonitor         $ddosUserMonitor,
        private readonly UrlGeneratorInterface   $urlGenerator,
        private readonly UrlMatcherService       $urlMatcherService
    ){}

    /**
     * If the route is public then this method will get called,
     * If the user requires being logged in, then will be called only when user is logged in,
     */
    public function onRequest(RequestEvent $requestEvent)
    {
        $banResponse = new BanResponse(Response::HTTP_MOVED_PERMANENTLY);

        $isIpHandled   = $this->handleDdosIp($banResponse);
        $isUserHandled = $this->handleDdosUser($banResponse, $requestEvent);

        if ($isIpHandled || $isUserHandled) {
            $originalResponse = $requestEvent->getResponse();

            $id          = uniqid();
            $redirectUrl = $this->urlGenerator->generate(TrapAction::ROUTE_NAME_ENDLESS_DREAM, [
                "id" => $id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $banResponse->setRedirectUrl($redirectUrl);
            $newResponse = $banResponse->toJsonResponse();

            if (!empty($originalResponse)) {
                $newResponse->headers->add($originalResponse->headers->all());
            }

            $requestEvent->setResponse($newResponse);
            $requestEvent->stopPropagation();
        }
    }

    /**
     * Handles the possible ddos based on the ip,
     * this is later used in two cases:
     * - front call, where user will be moved to special page with ban info,
     * - back call, which should not happen but if it will then user will be moved to endless sleep based page to choke the call,
     *
     * @param BanResponse  $banResponse
     *
     * @return bool
     */
    private function handleDdosIp(BanResponse $banResponse): bool
    {
        $ipBan = $this->ddosMonitor->isAbusiveCall();
        if ($ipBan) {
            $banResponse->addBanType(BanResponse::BAN_TYPE_IP);
            $banResponse->setValidTill($ipBan->getValidTill()?->format("Y-m-d H:i:s"));

            return true;
        }

        return false;
    }

    /**
     * Same as: {@see ExcessiveRequestListener::handleDdosIp()} but for user
     *
     * @param BanResponse  $banResponse
     * @param RequestEvent $requestEvent
     *
     * @return bool
     */
    private function handleDdosUser(BanResponse $banResponse, RequestEvent $requestEvent): bool
    {
        $calledRoute = $this->urlMatcherService->getRouteForCalledUri($requestEvent->getRequest()->getRequestUri());
        if (in_array($calledRoute, self::USER_CHECK_EXCLUDED_ROUTES)) {
            return false;
        }

        $userBan = $this->ddosUserMonitor->isAbusiveCall();
        if ($userBan) {
            $banResponse->addBanType(BanResponse::BAN_TYPE_USER);
            $banResponse->setValidTill($userBan->getValidTill()?->format("Y-m-d H:i:s"));

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                "onRequest", -48
            ],
        ];
    }

}