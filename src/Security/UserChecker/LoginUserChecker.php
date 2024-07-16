<?php

namespace App\Security\UserChecker;

use App\Entity\Security\User;
use App\Exception\AccountStatus\DeletedUserException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Performs additional checks on user for login logic,
 *
 * {@link https://symfony.com/doc/current/security/user_checkers.html}
 */
class LoginUserChecker implements UserCheckerInterface
{

    /**
     * {@inheritDoc}
     *
     * Check if given user is deleted, if yes then deny access,
     */
    public function checkPreAuth(UserInterface $user)
    {
        /** @var User $user */
        if ($user->isDeleted()) {
            throw new DeletedUserException("This user is deleted: " . $user->getId());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkPostAuth(UserInterface $user)
    {
        // nothing here
    }
}