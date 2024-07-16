<?php

namespace App\Service\System\Restriction;

use App\Action\Security\UserAction;
use App\Repository\Storage\PageTrackingStorageRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensures that the user registration process is not getting abused.
 * Only IP can be tracked here.
 */
class UserRegisterRestrictionService
{
    private const MIN_CHECK_OFFSET = 60;
    private const LOW_THRESHOLD_AMOUNT = 50;

    public function __construct(
        private readonly PageTrackingStorageRepository $pageTrackingStorageRepository
    ){}

    /**
     * @return bool
     */
    public function isExcessiveCall(): bool
    {
        $routeCallCount = $this->pageTrackingStorageRepository->getRecentCallCountForIp(
            self::MIN_CHECK_OFFSET,
            Request::createFromGlobals()->getClientIp(),
            UserAction::ROUT_NAME_REGISTER_USER,
        );

        return ($routeCallCount >= self::LOW_THRESHOLD_AMOUNT);
    }
}