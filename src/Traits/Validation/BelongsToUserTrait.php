<?php

namespace App\Traits\Validation;

use App\Entity\Security\User;
use App\Exception\Security\OtherUserResourceAccessException;
use Exception;

trait BelongsToUserTrait
{
    /**
     * Checks if given resource (example: entity) belongs to user,
     * if it does then nothing happen, else exception gets thrown
     *
     * @throws Exception
     * @throws OtherUserResourceAccessException
     */
    public function ensureBelongsToUser(User $comparedUser): void
    {
        if (!method_exists($this, 'getUser')) {
            $currClass = self::class;
            throw new Exception("This class `{$currClass}` has no method named `getUser`");
        }

        $user = $this->getUser();
        if (!($user instanceof User)) {
            return;
        }

        if ($user->getId() !== $comparedUser->getId()) {
            throw new OtherUserResourceAccessException();
        }
    }
}
