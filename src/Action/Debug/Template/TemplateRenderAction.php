<?php

namespace App\Action\Debug\Template;

use App\Service\Security\JwtAuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/debug", name: "debug_")]
class TemplateRenderAction extends AbstractController
{
    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Renders any template with provided data from request
     * @param Request $request
     *
     * @return Response
     */
    #[Route("/render-template", name: "render.template", methods: [Request::METHOD_GET])]
    public function renderTemplate(Request $request): Response {

        $requestDataJson = $request->getContent();
        $requestData     = json_decode($requestDataJson, true);

        $templatePath = $requestData["templatePath"];
        $templateData = $requestData["templateData"];

        // generic variables for all templates
        $templateData = array_merge(
            $templateData,
            [
                "user" => $this->jwtAuthenticationService->getUserFromRequest()
            ]
        );

        return $this->render($templatePath, $templateData);
    }
}