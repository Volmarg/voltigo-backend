<?php

namespace App\Action\Security;

use App\Attribute\AllowInactiveUser;
use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Services;
use App\Response\Base\BaseResponse;
use App\Response\Security\CsrfTokenResponse;
use App\Security\CsrfTokenManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use TypeError;

/**
 * Consist of system wide security logic
 *
 * Class SystemAction
 * @package App\Action\Security
 */
class SystemAction
{
    public const ROUTE_NAME_SYSTEM_GET_CSRF_TOKEN = "system_get_csrf_token";

    /**
     * @var CsrfTokenManagerInterface $csrfTokenManager
     */
    private CsrfTokenManagerInterface $csrfTokenManager;

    /**
     * @var Services $services
     */
    private Services $services;

    public function __construct(CsrfTokenManager $csrfTokenManager, Services $services)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->services         = $services;
    }

    /**
     * Will return the @param Request $request
     * @param string $tokenId
     * @return JsonResponse
     * @see CsrfTokenResponse containing the csrf token for form submission
     */
    #[Route("/system/security/get-csrf-token/{tokenId}", name: self::ROUTE_NAME_SYSTEM_GET_CSRF_TOKEN, methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    #[JwtAuthenticationDisabledAttribute]
    #[AllowInactiveUser]
    public function getCsrfToken(Request $request, string $tokenId): JsonResponse
    {
        try{
            $response = new CsrfTokenResponse();

            // Options does not use the token - no need to generate it
            if($request->getMethod() !== Request::METHOD_OPTIONS)
            {
                $token = $this->csrfTokenManager->refreshToken($tokenId);

                $response->prefillBaseFieldsForSuccessResponse();
                $response->setCsrfToken($token->getValue());
            }

            return $response->toJsonResponse();
        }catch(Exception | TypeError $e){
            $this->services->getLoggerService()->logException($e);
            return BaseResponse::buildInternalServerErrorResponse()->toJsonResponse();
        }

    }

}