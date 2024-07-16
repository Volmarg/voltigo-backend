<?php

namespace App\Listener\Kernel\Response;

use App\Controller\Core\Services;
use App\Controller\Storage\PageTrackingStorageController;
use App\Response\Ban\BanResponse;
use App\Response\Base\BaseResponse;
use App\Security\UriAuthenticator;
use App\Service\ResponseService;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles checking if the response types are correct,
 * It's expected that this API backend will return always JSON response of given structure
 * matching the:
 * @see BaseResponse::MINIMAL_FIELDS_FOR_VALID_BASE_API_RESPONSE
 *
 * In few cases other responses are allowed but their content keys must be equal to some of the keys used in the:
 * @see BaseResponse
 *
 * Class ResponseTypeListener
 * @package App\Listener\Kernel\Response
 */
class ResponseValidityListener implements EventSubscriberInterface
{
    private const REQUEST_CALLER = "Caller";

    // indicates that request is coming from front
    private const CALLER_FRONT = "Front";

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * @var PageTrackingStorageController $pageTrackingStorageController
     */
    private PageTrackingStorageController $pageTrackingStorageController;

    public function __construct(
        Services                         $services,
        PageTrackingStorageController    $pageTrackingStorageController,
        private readonly ResponseService $responseService
    )
    {
        $this->pageTrackingStorageController = $pageTrackingStorageController;
        $this->services                      = $services;
    }

    /**
     * Handles the response checking
     *
     * @param ResponseEvent $event
     * @throws Exception
     */
    public function onResponse(ResponseEvent $event): void
    {
        if( UriAuthenticator::isUriExcludedFromAuthenticationByRegex() ){
            return;
        }

        // options must ALWAYS be first, else it causes issues with CORS
        $request  = $event->getRequest();
        $response = $event->getResponse();
        if( Request::METHOD_OPTIONS === $request->getMethod() ){
            return;
        }

        if ($this->responseService->canHandleAsBaseResponse($request)) {
            return;
        }

        if ($this->isNonFrontRequest($event)) {
            $this->handleNonFrontRequest($event);
            $event->stopPropagation();
            return;
        }

        $responseContent = $response->getContent();
        $isJsonValid     = $this->services->getValidationService()->validateJson($responseContent);
        if(!$isJsonValid){
            $event->setResponse(BaseResponse::buildInternalServerErrorResponse()->toJsonResponse());
            $event->stopPropagation();
            $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($event->getRequest(), $event->getResponse());
            return;
        }

        $responseDataArray = json_decode($responseContent, true);
        if($response instanceof JWTAuthenticationFailureResponse){
            $this->validateFailureJwtResponse($event, $responseDataArray);
            return; // not validating any further
        }

        if($response instanceof JWTAuthenticationSuccessResponse){
            $this->validateSuccessJwtResponse($event, $responseDataArray);
            return; // not validating any further
        }

        if( !($response instanceof JsonResponse) ){
            $this->services->getLoggerService()->critical("Expected " . JsonResponse::class . " got: " . $response::class);
            $event->setResponse(BaseResponse::buildInternalServerErrorResponse()->toJsonResponse());
            $event->stopPropagation();

            $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($request, $event->getResponse());
            return; // not validating any further
        }

        $isJsonResponseValid = $this->validateJsonResponse($event, $responseDataArray);
        if(!$isJsonResponseValid){
            return; // not validating any further
        }
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                "onResponse" , -48
            ],
        ];
    }

    /**
     * Validate jwt failure response
     *
     * @param ResponseEvent $event
     * @param array $responseDataArray
     * @throws Exception
     */
    private function validateFailureJwtResponse(ResponseEvent $event, array $responseDataArray): void
    {
        if( !array_key_exists(BaseResponse::KEY_CODE, $responseDataArray) ){
            $this->services->getLoggerService()->critical("Key is missing in Jwt response", [
                "missingKey"        => BaseResponse::KEY_CODE,
                "responseDataArray" => $responseDataArray,
            ]);
            $event->setResponse(BaseResponse::buildInternalServerErrorResponse()->toJsonResponse());
            $event->stopPropagation();

            $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($event->getRequest(), $event->getResponse());
        }
    }

    /**
     * Validate jwt failure response
     *
     * @param ResponseEvent $event
     * @param array $responseDataArray
     * @throws Exception
     */
    private function validateSuccessJwtResponse(ResponseEvent $event, array $responseDataArray): void
    {
        if( !array_key_exists(BaseResponse::KEY_TOKEN, $responseDataArray) ){
            $this->services->getLoggerService()->critical("Key is missing in Jwt response", [
                "missingKey"        => BaseResponse::KEY_TOKEN,
                "responseDataArray" => $responseDataArray,
            ]);
            $event->setResponse(BaseResponse::buildInternalServerErrorResponse()->toJsonResponse());
            $event->stopPropagation();
            $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($event->getRequest(), $event->getResponse());
        }
    }

    /**
     * Validates the json response
     *
     * @param ResponseEvent $event
     * @param array $responseDataArray
     * @return bool - is valid or not
     * @throws Exception
     */
    private function validateJsonResponse(ResponseEvent $event, array $responseDataArray): bool
    {
        $responseDataKeys  = array_keys($responseDataArray);
        $commonKeys        = array_intersect($responseDataKeys, BaseResponse::MINIMAL_FIELDS_FOR_VALID_BASE_API_RESPONSE);

        $countOfCommonKeys   = count($commonKeys);
        $countOfRequiredKeys = count(BaseResponse::MINIMAL_FIELDS_FOR_VALID_BASE_API_RESPONSE);

        if($countOfCommonKeys !== $countOfRequiredKeys) {
            $this->services->getLoggerService()->critical("Not all required keys are present in json", [
                "requiredKeys"      => BaseResponse::MINIMAL_FIELDS_FOR_VALID_BASE_API_RESPONSE,
                "commonKeys"        => $commonKeys,
                "responseDataArray" => $responseDataArray,
                "info"              => [
                    "Are You sure that You are returning proper json?",
                    "Is Your response based on the " . BaseResponse::class . " ?"
                ]
            ]);
            $event->setResponse(BaseResponse::buildInternalServerErrorResponse()->toJsonResponse());
            $event->stopPropagation();
            $this->pageTrackingStorageController->setPageTrackingDataForResponseListener($event->getRequest(), $event->getResponse());
            return false;
        }

        return true;
    }

    /**
     * Check if request is coming from front (usually it will but if someone starts playing with the debug bar,
     * and will try to enter the backend page then it no longer is frontend call).
     * @param ResponseEvent $event
     *
     * @return bool
     */
    private function isNonFrontRequest(ResponseEvent $event): bool
    {
        return ($event->getRequest()->headers->get(self::REQUEST_CALLER) !== self::CALLER_FRONT);
    }

    /**
     * Handles the non frontend based requests
     * {@see ResponseValidityListener::isNonFrontRequest()} for more information
     *
     * @param ResponseEvent $event
     */
    private function handleNonFrontRequest(ResponseEvent $event): void
    {
        $originalResponse = $event->getResponse();
        $content          = $originalResponse->getContent();

        if ($this->services->getValidationService()->validateJson($content)) {

            $dataArray = json_decode($content, true);
            $targetFqn = $dataArray["fqn"] ?? null;
            if (empty($targetFqn)) {
                return;
            }

            $object = new $targetFqn();
            if ($object instanceof BaseResponse) {
                $object = $object::fromJson($content);

                if ($object instanceof BanResponse) {
                    $response = new SymfonyRedirectResponse($object->getRedirectUrl());
                    $event->setResponse($response);
                }
            }
        }
    }

}