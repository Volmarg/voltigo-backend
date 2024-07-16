<?php

namespace App\Entity\Storage\Ban;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Storage\Ban\BannedUserStorageRepository;

/**
 * @ORM\Entity(repositoryClass=BannedUserStorageRepository::class)
 */
class BannedUserStorage extends BaseBanStorage implements EntityInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="bannedUserStorage")
     */
    private User $user;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}