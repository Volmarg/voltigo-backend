<?php

namespace App\DTO\Frontend\Email\Template\Variables;

use App\Entity\Security\User;

/**
 * {@see User} based variables that will be available in email template editor
 */
class UserVariablesDTO
{
    /**
     * @var string $lastName
     */
    private string $firstName;

    /**
     * @var string $lastName
     */
    private string $lastName;

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * Creates {@see UserVariablesDTO} from {@see User}
     *
     * @param User $user
     *
     * @return static
     */
    public static function fromUserEntity(User $user): self
    {
        $dto = new self();

        $dto->setFirstName($user->getFirstname());
        $dto->setLastName($user->getLastname());

        return $dto;
    }
}