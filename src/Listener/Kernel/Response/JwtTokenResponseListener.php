<?php

namespace App\Listener\Kernel\Response;

use App\Action\Security\UserAction;
use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Services;
use App\Controller\Security\UserController;
use App\Controller\Storage\PageTrackingStorageController;
use App\Response\Base\BaseResponse;
use App\Response\Security\CsrfTokenResponse;
use App\Security\UriAuthenticator;
use App\Service\ResponseService;
use App\Service\Security\JwtAuthenticationService;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles the jwt token in response, will refresh the provided one and set it back in response
 *
 * Class FrontResponseListener
 * @package App\Listener
 */
class JwtTokenResponseListener implements EventSubscriberInterface
{
    const JWT_RESPONSE_KEY_TOKEN = "token";

    const ROUTES_EXCLUDED_FROM_TOKEN_REFRESH = [
      UserAction::ROUT_NAME_REMOVE_USER,
    ];

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * @var PageTrackingStorageController $pageTrackingStorageController
     */
    private PageTrackingStorageController $pageTrackingStorageController;

    /**
     * @var UserController $userController
     */
    private UserController $userController;

    /**
     * @var SerializerInterface $serializer
     */
    private SerializerInterface $serializer;

    public function __construct(
        Services                         $services,
        PageTrackingStorageController    $pageTrackingStorageController,
        UserController                   $userController,
        SerializerInterface              $serializer,
        private readonly ResponseService $responseService
    )
    {
        $this->pageTrackingStorageController = $pageTrackingStorageController;
        $this->userController                = $userController;
        $this->serializer                    = $serializer;
        $this->services                      = $services;
    }

    /**
     * Handles the response, attempts to refresh the token and append it to the base response dto json content
     *
     * @param ResponseEvent $event
     * @throws ReflectionException
     * @throws Exception
     */
    public function onResponse(ResponseEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        if (str_contains($request->getRequestUri(), "/api")) {
            return;
        }

        if( Request::METHOD_OPTIONS === $request->getMethod() ){
            return;
        }

        if( !($response instanceof JsonResponse) ){
            return;
        }

        if ($this->responseService->canHandleAsBaseResponse($request)) {
            return;
        }

        // The method checks if jwt token is there anyway
        if($response instanceof JWTAuthenticationSuccessResponse){
            $contentArray = json_decode($response->getContent(), true);
            if( $this->services->getValidationService()->validateJson($response->getContent()) ){
                $jwtToken = $contentArray[self::JWT_RESPONSE_KEY_TOKEN] ?? null;
                $this->userController->updateUserActivityFromJwtToken($jwtToken);
            }
        }

        if(
                UriAuthenticator::isUriExcludedFromAuthenticationByRegex() // must be first due to profiler falling in this case yet crashes for other checks (Symfony issue)
            ||  $this->services->getAttributeReaderService()->hasUriAttribute($request->getRequestUri(), JwtAuthenticationDisabledAttribute::class)
        ){
            return;
        }

        // might be csrf token response containing csrfToken
        $frontResponse = CsrfTokenResponse::fromJson($response->getContent());
        if( empty($frontResponse->getCsrfToken()) ){

            // that's because each api response must extend from base so this key must be present
            $dataArray = json_decode($response->getContent(), true);
            $fqn       = $dataArray[BaseResponse::KEY_FQN] ?? null;
            if( is_null($fqn) ){
                throw new Exception("Given response was not based on the parent: " . BaseResponse::class);
            }

            /**
             * this case happens when child class has no {@see BaseResponse::fromJson()} implemented and uses the parent one
             * that's desired effect as some child classes can have some special checks and only then they implement this method
             */
            $frontResponse = $fqn::fromJson($response->getContent());
            if($fqn !== $frontResponse::class){
                $frontResponse = $this->serializer->deserialize($response->getContent(), $fqn, "json");
            }
        }

        if( !$frontResponse->isSuccess() ){
            return;
        }

        $jwtToken = $this->services->getJwtAuthenticationService()->extractJwtFromRequest();
        if( empty($jwtToken) ){
            return;
        }

        $routeForUri = $this->services->getUrlMatcherService()->getRouteForCalledUri($request->getRequestUri());
        if(
                !empty($routeForUri)
            &&  in_array($routeForUri, self::ROUTES_EXCLUDED_FROM_TOKEN_REFRESH)
        ){
            return;
        }

        $this->userController->updateUserActivityFromJwtToken($jwtToken);

        try{
            $isTokenValid = $this->services->getJwtAuthenticationService()->isTokenValid($jwtToken);
            if(!$isTokenValid){
                throw new InvalidTokenException("This jwt token is not valid!");
            }

            $refreshedJwtToken = $this->services->getJwtAuthenticationService()->handleJwtTokenRefresh($jwtToken);
            $frontResponse->setToken($refreshedJwtToken);
            $frontResponse->setSuccess(true);
        }catch(Exception $e){

            if( JwtAuthenticationService::isJwtTokenException($e) ){

                // no matter what happens - cannot let the user in!
                $frontResponse->setCode(Response::HTTP_UNAUTHORIZED);
                $frontResponse->setMessage($e->getMessage());
            }else{

                $frontResponse->setCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $frontResponse->setRedirectRoute("Exception was thrown");
                $this->services->getLoggerService()->logException($e);
            }

            $frontResponse->setSuccess(false);
        }

        $event->setResponse($frontResponse->toJsonResponse());
        $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($event->getRequest(), $event->getResponse());
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                "onResponse" , -49
            ],
        ];
    }

}