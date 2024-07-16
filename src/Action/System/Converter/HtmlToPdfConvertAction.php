<?php

namespace App\Action\System\Converter;

use App\Enum\Service\Pdf\PdfGenerationSourceEnum;
use App\Exception\Lib\HtmlToImageException;
use App\Response\Base\BaseResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Shell\ShellCutyCapService;
use App\Service\Shell\ShellHtmlToMultiPagePdfService;
use App\Service\Shell\ShellImagickConvertService;
use App\Service\System\Restriction\EmailTemplateRestrictionService;
use App\Service\Validation\ValidationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/system-file/converter/", name: "system_file.converter.")]
class HtmlToPdfConvertAction extends AbstractController
{
    private const KEY_HTML_CONTENT = "htmlContent";
    private const KEY_SOURCE = "source";

    public function __construct(
        private readonly ShellHtmlToMultiPagePdfService  $shellHtmlToMultiPagePdfService,
        private readonly ShellCutyCapService             $shellCutyCapService,
        private readonly ShellImagickConvertService      $shellImagickConvertService,
        private readonly ValidationService               $validationService,
        private readonly TranslatorInterface             $translator,
        private readonly EmailTemplateRestrictionService $emailTemplateRestrictionService,
        private readonly LoggerInterface                 $logger,
        private readonly JwtAuthenticationService        $jwtAuthenticationService
    ) {

    }

    /**
     * This relies on: "wkhtmltopdf"
     *
     * Accepts the html source and converts it into pdf with multiple pages,
     * response returns base64 of the pdf file content.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws HtmlToImageException
     */
    #[Route("html-to-pdf-pages", name: "html_to_pdf_pages", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function htmlToPdfPages(Request $request): JsonResponse
    {
        $response = BaseResponse::buildOkResponse();
        $content = $request->getContent();
        if (!$this->validationService->validateJson($content)) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        $data        = json_decode($content, true);
        $htmlContent = $data[self::KEY_HTML_CONTENT] ?? null;
        $source      = $data[self::KEY_SOURCE] ?? null;

        $pdfNotAllowedResponse = $this->handleCannotGeneratePdf($source);
        if (!empty($pdfNotAllowedResponse)) {
            return $pdfNotAllowedResponse;
        }

        if (empty($htmlContent)) {
            return BaseResponse::buildBadRequestErrorResponse(
                $this->translator->trans('converter.message.htmlContentMissing')
            )->toJsonResponse();
        }

        $pdfBase64 = $this->shellHtmlToMultiPagePdfService->htmlToPdf($htmlContent);
        $response->setBase64($pdfBase64);

        return $response->toJsonResponse();
    }

    /**
     * This relies on: "html to image" conversion and "imagick" which converts image to pdf
     *
     * Accepts the html source and converts it into pdf with single pages,
     * response returns base64 of the pdf file content.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws HtmlToImageException
     */
    #[Route("html-to-single-pdf", name: "html_to_single_pdf", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function htmlToSinglePdf(Request $request): JsonResponse
    {
        $response = BaseResponse::buildOkResponse();
        $content = $request->getContent();
        if (!$this->validationService->validateJson($content)) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        $data        = json_decode($content, true);
        $htmlContent = $data[self::KEY_HTML_CONTENT] ?? null;
        $source      = $data[self::KEY_SOURCE] ?? null;

        $pdfNotAllowedResponse = $this->handleCannotGeneratePdf($source);
        if (!empty($pdfNotAllowedResponse)) {
            return $pdfNotAllowedResponse;
        }

        if (empty($htmlContent)) {
            return BaseResponse::buildBadRequestErrorResponse(
                $this->translator->trans('converter.message.htmlContentMissing')
            )->toJsonResponse();
        }

        $tmpFilePath        = "/tmp/" . uniqid() . "." . ShellCutyCapService::DEFAULT_EXTENSION;
        $base64ImageContent = $this->shellCutyCapService->htmlToBase64($htmlContent);

        file_put_contents($tmpFilePath, base64_decode($base64ImageContent));

        $pdfBase64 = $this->shellImagickConvertService->getBase64($tmpFilePath, "pdf");
        $response->setBase64($pdfBase64);

        unlink($tmpFilePath);

        return $response->toJsonResponse();
    }

    /**
     * @param string $source
     *
     * @return JsonResponse|null
     */
    private function handleCannotGeneratePdf(string $source): ?JsonResponse
    {
        if(
                (PdfGenerationSourceEnum::tryFrom($source)?->name === PdfGenerationSourceEnum::EMAIL_TEMPLATE->name)
            &&  !$this->emailTemplateRestrictionService->canGeneratePdf()
        ) {
            $user = $this->jwtAuthenticationService->getUserFromRequest();
            $this->logger->critical("User tried to generate PDF from E-Mail template, yet he is not allowed to. User: {$user->getId()}");
            return BaseResponse::buildBadRequestErrorResponse()->toJsonResponse();
        }

        return null;
    }

}