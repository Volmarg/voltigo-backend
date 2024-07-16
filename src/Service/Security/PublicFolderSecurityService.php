<?php

namespace App\Service\Security;


use App\Entity\Security\User;
use App\Exception\Security\PublicFolderAccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles restricting access to the public folder
 */
class PublicFolderSecurityService
{
    private const JWT_TOKEN_QUERY_KEY = "token";

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * @throws PublicFolderAccessDeniedException
     */
    public function denyIfNotLogged(Request $request): void
    {
        $user = $this->getUserFromToken($request);
        if (empty($user)) {
            throw new PublicFolderAccessDeniedException("Not logged in!");
        }
    }

    /**
     * @param Request $request
     *
     * @return User|null
     *
     * @throws PublicFolderAccessDeniedException
     */
    public function getUserFromToken(Request $request): ?User
    {
        if (!$request->query->has(self::JWT_TOKEN_QUERY_KEY)) {
            throw new PublicFolderAccessDeniedException("Jwt token is missing");
        }

        $token = $request->query->get(self::JWT_TOKEN_QUERY_KEY);
        $user  = $this->jwtAuthenticationService->getUserForToken($token);

        return $user;
    }

    /**
     * @throws PublicFolderAccessDeniedException
     */
    public function validateFilePath(string $path): void
    {
        if (str_starts_with($path, ".")) {
            throw new PublicFolderAccessDeniedException("Provided file path violates security concerns. Path start with DOT: {$path}");
        }
    }
}