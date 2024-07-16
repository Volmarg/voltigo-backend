<?php

namespace App\Action\System;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Response\System\Security\GetPasswordConstraintsResponse;
use App\Service\Security\PasswordGeneratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SecurityAction
{
    public const ROUTE_NAME_GET_PASSWORD_CONSTRAINT = "system.get_password_constraints";

    public function __construct(
        private readonly PasswordGeneratorService $passwordGeneratorService
    ){}

    /**
     * Will return response that contains array of password constraints
     *
     * @return JsonResponse
     */
    #[Route("/system/get-password-constraints", name: self::ROUTE_NAME_GET_PASSWORD_CONSTRAINT, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    public function getPasswordConstraints(): JsonResponse
    {
        $response = GetPasswordConstraintsResponse::buildOkResponse();
        $response->setConstraints($this->passwordGeneratorService->getConstraintTexts());

        return $response->toJsonResponse();
    }
}
