<?php

namespace App\Controller\Storage;

use App\Action\Security\UserAction;
use App\Controller\Core\Services;
use App\Entity\Storage\PageTrackingStorage;
use App\Listener\Kernel\Request\PageTrackingRequestListener;
use App\Repository\Storage\PageTrackingStorageRepository;
use App\Response\Base\BaseResponse;
use App\Service\ResponseService;
use App\Service\Routing\UrlMatcherService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageTrackingStorageController
 * @package App\Controller\Storage
 */
class PageTrackingStorageController extends AbstractController
{
    const ROUTES_EXCLUDED_FROM_PAGE_TRACKING = [
        /**
         * Must be excluded as it is called before kernel.request
         * thus might crash on authentication before reaching page tracking logic
         */
        UserAction::ROUTE_NAME_LOGIN,
    ];

    /**
     * @var PageTrackingStorageRepository $pageTrackingStorageRepository
     */
    private PageTrackingStorageRepository $pageTrackingStorageRepository;

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * PageTrackingStorageController constructor.
     *
     * @param PageTrackingStorageRepository $pageTrackingStorageRepository
     * @param Services                      $services
     * @param UrlMatcherService             $urlMatcherService
     * @param ResponseService               $responseService
     */
    public function __construct(
        PageTrackingStorageRepository      $pageTrackingStorageRepository,
        Services                           $services,
        private readonly UrlMatcherService $urlMatcherService,
        private readonly ResponseService   $responseService
    )
    {
        $this->services                      = $services;
        $this->pageTrackingStorageRepository = $pageTrackingStorageRepository;
    }

    /**
     * Will save or update the entry in db
     *
     * @param PageTrackingStorage $pageTrackingStorage
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(PageTrackingStorage $pageTrackingStorage): void
    {
        $this->pageTrackingStorageRepository->save($pageTrackingStorage);
    }

    /**
     * Will build the page tracking storage entry from request
     *
     * @param Request $request
     * @return PageTrackingStorage
     */
    public function buildFromRequest(Request $request): PageTrackingStorage
    {
        $matchingRoute = $this->urlMatcherService->getRouteForCalledUri($request->getRequestUri());

        $pageTrackingStorage = new PageTrackingStorage();
        $pageTrackingStorage->setIp($request->getClientIp());
        $pageTrackingStorage->setRequestContent($request->getContent());
        $pageTrackingStorage->setRequestUri($request->getRequestUri());
        $pageTrackingStorage->setRouteName($matchingRoute);
        $pageTrackingStorage->setMethod($request->getMethod());
        $pageTrackingStorage->setHeaders($request->headers->all());
        $pageTrackingStorage->setQueryParameters($request->query->all());
        $pageTrackingStorage->setRequestParameters($request->request->all());

        $user     = null;
        $jwtToken = $this->services->getJwtAuthenticationService()->extractJwtFromRequest();
        if( !empty($jwtToken) ){
            $user = $this->services->getJwtAuthenticationService()->getUserForToken($jwtToken);
        }

        $pageTrackingStorage->setUser($user);

        return $pageTrackingStorage;
    }

    /**
     * This method handles updating page tracking entry for response event
     * Should only be used in response call!
     *
     * @param Request $request
     * @param Response $response
     * @throws Exception
     */
    public function setPageTrackingDataForResponseListener(Request $request, Response $response): void
    {
        if( !$this->isAllowedToBeTracked($request) ){
            return;
        }

        if( !$request->headers->has(PageTrackingRequestListener::HEADER_PAGE_TRACKING_ID) ){
            $this->services->getLoggerService()->warning("Could not save updated page tracking on response", [
                "option 1" => "Request is missing page tracking id. Probably id was not set in request or method is incorrectly called!",
                "option 2" => "Authentication might've failed and it's being executed BEFORE kernel request get executed"
            ]);
            return;
        }

        $pageTrackingId = $request->headers->get(PageTrackingRequestListener::HEADER_PAGE_TRACKING_ID);
        $usedCode       = $response->getStatusCode();
        $json           = $response->getContent();

        if(
                $this->responseService->canHandleAsBaseResponse($request)
            &&  $this->services->getValidationService()->validateJson($json)
        ){
            $baseApiResponse = BaseResponse::fromJson($response->getContent());
            $usedCode        = $baseApiResponse->getCode() ;
        }

        $this->pageTrackingStorageRepository->updateField(['responseCode' => $usedCode], $pageTrackingId);
    }

    /**
     * Return information if page tracking can be handled for given request
     *
     * @param Request $request
     * @return bool
     */
    public function isAllowedToBeTracked(Request $request): bool
    {
        $isRouteExcludedFromPageTracking = in_array(
            $this->services->getUrlMatcherService()->getRouteForCalledUri($request->getRequestUri()),
            self::ROUTES_EXCLUDED_FROM_PAGE_TRACKING
        );

        if(
                $request->getMethod() !== Request::METHOD_OPTIONS
            &&  !$isRouteExcludedFromPageTracking
        ){
            return true;
        }

        return false;
    }
}