<?php

namespace App\Action\Debug\Service;

use App\Exception\Security\MaliciousFileException;
use App\Response\Base\BaseResponse;
use App\Service\Security\Scanner\FileScannerService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/debug", name: "debug_")]
class FileScannerAction extends AbstractController
{
    public function __construct(
        private readonly FileScannerService $fileScannerService,
        private readonly KernelInterface    $kernel
    ){}

    /**
     * Check if the scanner is reachable / available / can it be used at all
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/service/file-scanner/check-availability", name: "service.file.scanner.check.availability", methods: Request::METHOD_GET)]
    public function checkAvailability(): JsonResponse
    {
        $baseResponse = BaseResponse::buildOkResponse();

        try{
            $isValid      = $this->fileScannerService->scan($this->kernel->getProjectDir() . "/composer.json");
            $baseResponse->setData([
                "isValid" => $isValid,
            ]);
        } catch (Exception $e) {
            if($e instanceof MaliciousFileException){
                $baseResponse->setData([
                    "isValid" => false,
                ]);
                return $baseResponse->toJsonResponse();
            }

            throw $e;
        }

        return $baseResponse->toJsonResponse();
    }
}