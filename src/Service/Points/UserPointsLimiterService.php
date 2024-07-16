<?php

namespace App\Service\Points;

use App\Service\Security\JwtAuthenticationService;

/**
 * This service allows limiting / controlling amount of points that user can have on his account.
 * This was created as an idea of preventing users from buying insane amounts of points and crying later
 * that they would like to have re-found
 */
class UserPointsLimiterService
{
    // last max was 2000 (max should always go UP, otherwise it will lead to issues where users would have more than max allowed)
    public const MAX_POINTS_PER_USER = 2000;


    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Check if user can buy give amounts of points
     *
     * @param int $addedPointsAmount
     *
     * @return bool
     */
    public function canBuyPoints(int $addedPointsAmount): bool {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        return (($user->getPointsAmountWithPendingOnes() + $addedPointsAmount) <= self::MAX_POINTS_PER_USER);
    }
}