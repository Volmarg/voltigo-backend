<?php

namespace App\Listener\Kernel\Exception;

use App\Controller\Core\Services;
use App\Exception\Payment\PointShop\NotEnoughPointsException;
use App\Exception\Security\OtherUserResourceAccessException;
use App\Exception\Security\PublicFolderAccessDeniedException;
use App\Response\Api\BaseApiResponse;
use App\Response\Base\BaseResponse;
use App\Service\Security\JwtAuthenticationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exceptions handling class
 *
 * Class ExceptionListener
 * @package App\Listener
 */
class ExceptionListener implements EventSubscriberInterface
{
    /**
     * If an exception contains this string in the message then it won't be logged, will just be skipped,
     * some exceptions are just false positive, can be discarded, no need to get spammed by 404, etc.
     */
    private const EXCLUDED_STRINGS = [
        "Full authentication is required to access this resource.", // user tries to do something without being logged-in
    ];

    private Services $services;

    /**
     * ExceptionListener constructor.
     *
     * @param Services            $services
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Services $services,
        private readonly TranslatorInterface $translator
    )
    {
        $this->services = $services;
    }

    /**
     * Handles the exceptions
     *
     * @param ExceptionEvent $event
     */
    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($this->handleApiCall($event)) {
            return;
        }

        if ($this->handleOtherUserResourceAccess($event)) {
            return;
        }

        if (
                $exception instanceof NotFoundHttpException
            ||  $exception instanceof PublicFolderAccessDeniedException
            ||  $exception instanceof NotEnoughPointsException
        ) {
            $msg      = trim(preg_replace("#[\n ]{1,}#", " ", $exception->getMessage()));
            $response = BaseResponse::buildBadRequestErrorResponse($msg)->toJsonResponse();
        } else {
            $response = BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();

            if (!in_array($exception->getMessage(), self::EXCLUDED_STRINGS)) {
                $this->services->getLoggerService()->logException($exception);
            }
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                "onException", -1
            ],
        ];
    }

    /**
     * Handles the exception thrown when calling the api (the one that other projects are calling - not front calling back)
     *
     * @param ExceptionEvent $event
     *
     * @return bool - true means that it was api call, false otherwise
     */
    private function handleApiCall(ExceptionEvent $event): bool
    {
        $exception = $event->getThrowable();
        if (str_starts_with($event->getRequest()->getRequestUri(), "/api")) {
            $this->services->getLoggerService()->logException($exception, [
                "info" => "API CALL"
            ]);

            if (JwtAuthenticationService::isJwtTokenException($exception)) {
                $response = BaseApiResponse::buildBadRequestResponse($exception->getMessage());
                $response->setCode(Response::HTTP_UNAUTHORIZED);

                $event->setResponse($response->toJsonResponse());
                return true;
            }

            $apiResponse = BaseApiResponse::buildInternalServerResponse();
            if ($exception->getCode() >= 400 && $exception->getCode() < 500) {
                $apiResponse = BaseApiResponse::buildBadRequestResponse($exception->getMessage());
            }

            $event->setResponse($apiResponse->toJsonResponse());
            return true;
        }

        return false;
    }

    /**
     * Covers this exception explicitly: {@see OtherUserResourceAccessException}
     * which is thrown when user A tries to get some data of user B
     * - other words, for example: user A would try to get search results of user B via url (since it's just and ID in url)
     *
     * @param ExceptionEvent $event
     *
     * @return bool - true means that it was the resource exception, false otherwise
     */
    private function handleOtherUserResourceAccess(ExceptionEvent $event): bool
    {
        $exception = $event->getThrowable();
        if ($exception instanceof OtherUserResourceAccessException) {

            $response = BaseResponse::buildBadRequestErrorResponse($this->translator->trans('generic.notAllowedToPerformThisAction'));
            $event->setResponse($response->toJsonResponse());
            $event->stopPropagation();

            return true;
        }

        return false;
    }

}