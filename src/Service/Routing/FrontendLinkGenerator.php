<?php

namespace App\Service\Routing;

use App\Entity\Security\User;
use App\Service\Security\JwtAuthenticationService;
use App\Vue\VueRoutes;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

/**
 * Handles building / generating links for frontend based pages
 */
class FrontendLinkGenerator
{
    public const PAYLOAD_KEY_NEW_EMAIL_ADDRESS = 'newEmailAddress';

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Will generate link to email change confirmation page - link will be valid as long as the token payload allows for it
     *
     * @param User   $user
     * @param string $newEmailAddress
     *
     * @return string
     * @throws JWTDecodeFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function generateEmailChangeConfirmationLink(User $user, string $newEmailAddress): string
    {
        $extraPayload = [
            self::PAYLOAD_KEY_NEW_EMAIL_ADDRESS => $newEmailAddress,
        ];

        $url = VueRoutes::buildFrontendUrlForRoute(VueRoutes::ROUTE_PATH_USER_EMAIL_CHANGE_CONFIRMATION, [
            VueRoutes::ROUTE_PARAMETER_TOKEN => $this->jwtAuthenticationService->buildTokenForUser($user, $extraPayload),
        ]);

        return $url;
    }

}