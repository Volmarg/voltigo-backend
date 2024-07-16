<?php

namespace App\Listener\Kernel\Request;

use App\Action\Security\PublicFolderAction;
use App\Action\Security\SystemAction;
use App\Action\Security\UserAction;
use App\Action\Storage\FrontendErrorStorageAction;
use App\Action\System\SecurityAction;
use App\Action\System\SystemGeoDataAction;
use App\Action\System\SystemStateAction;
use App\Action\User\Setting\BaseDataAction;
use App\Attribute\AllowInactiveUser;
use App\Controller\Core\Env;
use App\Controller\Core\Services;
use App\Listener\Kernel\Request\Constants\FrontSecurityRequestListenerConstants;
use App\Response\Base\BaseResponse;
use App\Security\UriAuthenticator;
use App\Service\Security\CsrfTokenService;
use App\Service\Security\FrontendDecryptor;
use App\Service\Security\JwtAuthenticationService;
use Doctrine\ORM\ORMException;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestListener
 * @package App\Listener\Kernel\Request
 */
class FrontSecurityRequestListener implements EventSubscriberInterface
{

    const NAMES_OF_ROUTES_ALLOWED_TO_PASS_WITHOUT_CSRF_TOKEN = [
        SystemAction::ROUTE_NAME_SYSTEM_GET_CSRF_TOKEN,
        SystemStateAction::ROUTE_NAME_IS_SYSTEM_DISABLED,
        UserAction::ROUTE_NAME_RESET_PASSWORD,
        BaseDataAction::ROUTE_NAME_EMAIL_CHANGE,
        UserAction::ROUTE_NAME_ACTIVATE_USER,
        PublicFolderAction::ROUTE_NAME_DOWNLOAD,
        SecurityAction::ROUTE_NAME_GET_PASSWORD_CONSTRAINT,
        SystemGeoDataAction::ROUTE_NAME_GET_INTERNALLY_SUPPORTED_COUNTRIES,
        FrontendErrorStorageAction::ROUTE_NAME_INSERT_FRONTEND_ERROR,
    ];

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * RequestListener constructor.
     *
     * @param Services                 $services
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function __construct(
        Services                                  $services,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
    )
    {
        $this->services = $services;
    }

    /**
     * Handles the request logic
     *
     * @param RequestEvent $requestEvent
     * @throws Exception
     */
    public function handleSecurityCheck(RequestEvent $requestEvent): void
    {
        if (str_contains($requestEvent->getRequest()->getRequestUri(), "/api")) {
            return;
        }

        # Prevent OPTIONS from calling logic - yet OPTIONS must be defined on route to prevent Symfony from throwing 404 errors or method not allowed
        if( $requestEvent->getRequest()->getMethod() === Request::METHOD_OPTIONS )
        {
            $requestEvent->setResponse(BaseResponse::buildOkResponse()->toJsonResponse());
            $requestEvent->stopPropagation();
            return;
        }

        $isCsrfValidationOk = $this->validateCsrfToken($requestEvent);
        if(!$isCsrfValidationOk){
            return;
        }

        $isRequestOriginOk = $this->checkRequestOrigin($requestEvent);
        if(!$isRequestOriginOk){
            return;
        }

        $isInactiveUserAllowed = $this->isInactiveUserAllowed($requestEvent);
        if (!$isInactiveUserAllowed) {
            return;
        }

        $this->decryptPostRequestBag($requestEvent);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                "handleSecurityCheck", -50
            ],
        ];
    }

    /**
     * Will handle validating csrf token
     *
     * @param RequestEvent $requestEvent
     * @return bool
     * @throws ORMException
     */
    private function validateCsrfToken(RequestEvent $requestEvent): bool {
        return true; // open source
        $request              = $requestEvent->getRequest();
        $foundRouteNameForUri = $this->services->getUrlMatcherService()->getRouteForCalledUri($request->getRequestUri());

        $canForceSkipCsrf = ($request->query->has(FrontSecurityRequestListenerConstants::KEY_SKIP_CSRF) && Env::isDev());
        if ($canForceSkipCsrf) {
            return true;
        }

        if(
                !in_array($foundRouteNameForUri, self::NAMES_OF_ROUTES_ALLOWED_TO_PASS_WITHOUT_CSRF_TOKEN)
            &&  !UriAuthenticator::isUriExcludedFromAuthenticationByRegex()
            &&  $request->getMethod() !== Request::METHOD_OPTIONS
        ){
            $accessUnauthorizedJsonResponse = BaseResponse::buildUnauthorizedResponse()->toJsonResponse();
            if( !$request->headers->has(CsrfTokenService::KEY_CSRF_TOKEN) ){

                $this->services->getLoggerService()->warning("Request header is missing csrf related key: " . CsrfTokenService::KEY_CSRF_TOKEN);
                $requestEvent->setResponse($accessUnauthorizedJsonResponse);
                $requestEvent->stopPropagation();
                return false;
            }elseif( !$request->headers->has(CsrfTokenService::KEY_CSRF_TOKEN_ID) ){

                $this->services->getLoggerService()->warning("Request header is missing csrf related key: " . CsrfTokenService::KEY_CSRF_TOKEN_ID);
                $requestEvent->setResponse($accessUnauthorizedJsonResponse);
                $requestEvent->stopPropagation();
                return false;
            }

            $tokenId = $request->headers->get(CsrfTokenService::KEY_CSRF_TOKEN_ID);
            $token   = $request->headers->get(CsrfTokenService::KEY_CSRF_TOKEN);

            if( !$this->services->getCsrfTokenService()->isCsrfTokenValid($tokenId, $token) ){
                $this->services->getLoggerService()->warning("Access denied for csrf token", [
                    CsrfTokenService::KEY_CSRF_TOKEN    => $token,
                    CsrfTokenService::KEY_CSRF_TOKEN_ID => $tokenId,
                ]);

                $requestEvent->setResponse($accessUnauthorizedJsonResponse);
                $requestEvent->stopPropagation();
                return false;
            }

            // no validation handled
            return true;
        }

        return true;
    }

    /**
     * Will check if request is overall coming from fronted,
     * This will deny calls from any external tools / locally etc.
     * Only one domain defined in `.env` should've access to the backend
     *
     * @param RequestEvent $requestEvent
     * @return bool
     */
    private function checkRequestOrigin(RequestEvent $requestEvent): bool
    {
        return true; // open source
        $request = $requestEvent->getRequest();
        $referer = $request->server->get('HTTP_REFERER');

        if(
                $referer === Env::getFrontendBaseUrl()
            ||  $referer === Env::getFrontendBaseUrl() . "/"
            ||  Env::isDev() // don't care about origin if on dev
        ){
            return true;
        }

        $requestEvent->stopPropagation();
        $requestEvent->setResponse(BaseResponse::buildUnauthorizedResponse()->toJsonResponse());
        return false;
    }

    /**
     * Check if inactive user is allowed to call given url
     *
     * @param RequestEvent $requestEvent
     *
     * @return bool
     *
     * @throws ReflectionException
     * @throws JWTDecodeFailureException
     */
    private function isInactiveUserAllowed(RequestEvent $requestEvent): bool
    {
        if (empty($this->jwtAuthenticationService->extractJwtFromRequest())) {
            return true;
        }

        // this is required as otherwise this function fails since it tries to get the user from request later on
        $token = $this->jwtAuthenticationService->extractJwtFromRequest();
        if ($this->jwtAuthenticationService->isTokenExpired($token)) {
            return true;
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if ($user->isActive()) {
            return true;
        }

        $isAllowed = (
                !$user->isActive()
            &&  $this->services->getAttributeReaderService()->hasUriAttribute(
                    $requestEvent->getRequest()->getRequestUri(), AllowInactiveUser::class
            )
        );

        if (!$isAllowed) {
            $requestEvent->stopPropagation();
            $requestEvent->setResponse(BaseResponse::buildUnauthorizedResponse()->toJsonResponse());
        }

        return $isAllowed;
    }

    /**
     * Will decrypt the post request data
     *
     * @param RequestEvent $requestEvent
     * @throws Exception
     */
    private function decryptPostRequestBag(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();
        if(
                $request->headers->has(FrontendDecryptor::HEADER_ENCRYPTED_WITH)
            &&  ( $request->headers->get(FrontendDecryptor::HEADER_ENCRYPTED_WITH) === FrontendDecryptor::HEADER_ENCRYPTED_WITH_VALUE_RSA )
            &&  $request->getMethod() === Request::METHOD_POST
        )
        {
            $requestContent = $request->getContent();
            if( empty($requestContent) ){
                return;
            }

            $dataArray   = json_decode($requestContent, true);
            $isJsonValid = $this->services->getValidationService()->validateJson($requestContent);
            if(!$isJsonValid){
                $response = BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
                $requestEvent->setResponse($response);
                $requestEvent->stopPropagation();
                return;
            }

            $decryptedArray = $this->services->getFrontendDecryptor()->decryptArrayValues($dataArray);
            $decryptedJson  = json_encode($decryptedArray);

            // dirty way to set content by calling `this` from context of request, but there is no other easy way without loosing headers etc
            $requestClosure = function() use ($decryptedJson) {
                /** @phpstan-ignore-next-line */
                $this->content = $decryptedJson;
                return $this;
            };
            $request = $requestClosure->call($request);

            // same with setting the modified request
            $requestClosure = function() use ($request){
                /** @phpstan-ignore-next-line */
                $this->request = $request;
                return $this;
            };

            /**
             * Despite the IDE highlighting variable as not used, it's important to leave it as it's being replaced
             * by the return from closure
             *
             * @var RequestEvent $requestEvent
             */
            $requestEvent = $requestClosure->call($requestEvent);
        }
    }
}