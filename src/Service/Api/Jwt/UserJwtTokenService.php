<?php

namespace App\Service\Api\Jwt;

use App\Entity\Security\ApiUser;
use App\Security\LexitBundleJwtTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;

/**
 * Provides logic for handling jwt tokens in context of {@see ApiUser}
 */
class UserJwtTokenService
{
    /**
     * This name is necessary for the {@see LexitBundleJwtTokenAuthenticator} to work properly.
     * Now keep in mind that in reality the API based Token is not using E-Mail to identify user, but it's name
     * {@see ApiUser::getUsername()}.
     *
     * The problem is that {@see LexitBundleJwtTokenAuthenticator} was first added to the project in order to
     * communicate with the frontend.
     *
     * Only later it was required to get API for THIS project. This means that both frontend and API code
     * are use the same vendor package which can be configured only once per project.
     *
     * That's why the "email" is used here, as it was already configured this way for front. It's not really changing
     * anything of how code works, it's just confusing.
     */
    const USER_IDENTIFIER = "email";

    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ){}

    /**
     * Will create the jwt token for {@see ApiUser}
     *
     * @param ApiUser $user
     * @param bool $endlessLifetime
     *
     * @return string
     *
     * @throws JWTEncodeFailureException
     */
    public function generate(ApiUser $user, bool $endlessLifetime = false): string
    {
        return $this->jwtTokenService->encode([
            self::USER_IDENTIFIER => $user->getUserIdentifier(),
        ], $endlessLifetime);
    }

    /**
     * Will extract the E-Mail Address string from the jwt token payload and return it
     * If {@see UserJwtTokenService::USER_IDENTIFIER} is missing, then NULL will be returned
     *
     * @param string $jwtToken
     *
     * @return string|null
     * @throws JWTDecodeFailureException
     */
    public function getUserIdentifier(string $jwtToken): ?string
    {
        $payload = $this->jwtTokenService->decode($jwtToken);

        return $payload[self::USER_IDENTIFIER] ?? null;
    }

}
