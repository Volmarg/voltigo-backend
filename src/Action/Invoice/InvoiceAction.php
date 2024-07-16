<?php

namespace App\Action\Invoice;

use App\Response\Base\BaseResponse;
use App\Response\Invoice\GetInvoiceResponse;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Finances\Invoice\InvoiceService;
use App\Service\Security\JwtAuthenticationService;
use Exception;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides endpoints for uploaded file related logic
 */
class InvoiceAction extends AbstractController
{

    public function __construct(
        private readonly FinancesHubService       $financesHubService,
        private readonly InvoiceService           $invoiceService,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ) {
    }

    /**
     * Provides user uploaded invoice file path
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/invoice/get/{id}", name: "invoice.get", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getInvoice(int $id): JsonResponse
    {
        try {
            $user                = $this->jwtAuthenticationService->getUserFromRequest();
            $existingInvoicePath = $this->invoiceService->checkAndGetExistingInvoicePdf($user->getId(), $id);
            $response            = GetInvoiceResponse::buildOkResponse();
            if (!empty($existingInvoicePath)) {
                $response->setInvoicePathInPublic($existingInvoicePath);
                return $response->toJsonResponse();
            }

            $pdfContent  = $this->financesHubService->getInvoicePdfContent($id);
            $invoicePath = $this->invoiceService->saveInvoicePdf($pdfContent, $id);

            $response->setInvoicePathInPublic($invoicePath);
        } catch (FinancesHubBridgeException $fhde) {
            if ($fhde->getCode() >= 400 && $fhde->getCode() < 500) {
                return (GetInvoiceResponse::buildBadRequestErrorResponse($fhde->getMessage()))->toJsonResponse();
            }

            throw $fhde;
        }

        return $response->toJsonResponse();
    }

}