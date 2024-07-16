<?php

namespace App\Service\Cleanup;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use Exception;

/**
 * Handles cleaning the {@see User}
 */
class UserCleanupService implements CleanupServiceInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly int            $maxLifetimeHoursSinceDeleted
    ){}

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function cleanUp(): int
    {
        return $this->userRepository->removeOlderThanHours($this->maxLifetimeHoursSinceDeleted);
    }
}