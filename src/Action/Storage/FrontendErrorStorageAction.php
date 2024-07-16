<?php

namespace App\Action\Storage;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Services;
use App\Controller\Storage\FrontendErrorStorageController;
use App\Entity\Storage\FrontendErrorStorage;
use App\Response\Base\BaseResponse;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains logic for handling fronted error storage calls
 *
 * Class FrontendErrorStorageAction
 * @package App\Action\Storage
 */
class FrontendErrorStorageAction extends AbstractController
{
    public const ROUTE_NAME_INSERT_FRONTEND_ERROR = "storage_insert_frontend_error_storage_data";

    /**
     * @var FrontendErrorStorageController $frontendErrorStorageController
     */
    private FrontendErrorStorageController $frontendErrorStorageController;

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * FrontendErrorStorageAction constructor.
     * @param FrontendErrorStorageController $frontendErrorStorageController
     * @param Services $services
     */
    public function __construct(FrontendErrorStorageController $frontendErrorStorageController, Services $services)
    {
        $this->frontendErrorStorageController = $frontendErrorStorageController;
        $this->services                       = $services;
    }

    /**
     * Handles inserting frontend data into frontend error storage database table
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[JwtAuthenticationDisabledAttribute]
    #[Route("/storage/insert-frontend-error-storage-data", name: self::ROUTE_NAME_INSERT_FRONTEND_ERROR)]
    public function insertFrontendErrorStorageData(Request $request): JsonResponse
    {
        $requestContent = $request->getContent();
        if( !$this->services->getValidationService()->validateJson($requestContent) ){
            return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
        }

        $dataArray = json_decode($requestContent, true);

        $frontendErrorStorage = new FrontendErrorStorage();
        $frontendErrorStorage->setData($dataArray);

        $this->frontendErrorStorageController->save($frontendErrorStorage);

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

}