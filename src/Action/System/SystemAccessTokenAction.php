<?php

namespace App\Action\System;

use App\Entity\Security\User;
use App\Response\System\AccessToken\GetPublicFolderAccessTokenResponse;
use App\Service\Security\JwtAuthenticationService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SystemAccessTokenAction
{
    public function __construct(
        private readonly JwtAuthenticationService       $jwtAuthenticationService
    ){}

    /**
     * Returns the jwt token used only for public folder access (token won't work for the normal access to the project),
     *
     * @return JsonResponse
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws JWTDecodeFailureException
     */
    #[Route("/system/access-token/get-for-public-folder", name: "system.access.get_for_public_folder", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getPublicFolderAccessToken(): JsonResponse
    {
        $user                    = $this->jwtAuthenticationService->getUserFromRequest();
        $publicFolderAccessToken = $this->jwtAuthenticationService->buildWithRolesForUser($user, [User::RIGHT_PUBLIC_FOLDER_ACCESS], false);
        $response                = GetPublicFolderAccessTokenResponse::buildOkResponse();

        $response->setPublicFolderAccessToken($publicFolderAccessToken);
        return $response->toJsonResponse();
    }
}