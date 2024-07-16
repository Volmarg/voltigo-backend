<?php

namespace App\Entity\Ecommerce\Account;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Entity\Traits\IsActiveTrait;
use App\Entity\Traits\SoftDeletableTrait;
use App\Repository\Ecommerce\Account\AccountRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contains the information about the user account can be for example such data as:
 * - premium / standard / free
 * - avatar / isBanned
 *
 * @ORM\Entity(repositoryClass=AccountRepository::class)
 */
class Account implements EntityInterface
{
    use CreatedAndModifiedTrait;
    use SoftDeletableTrait;
    use IsActiveTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var AccountType
     * @ORM\ManyToOne(targetEntity=AccountType::class, inversedBy="accounts")
     * @ORM\JoinColumn(nullable=false)
     */
    private AccountType $type;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="account", cascade={"persist", "remove"})
     */
    private User $user;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?AccountType
    {
        return $this->type;
    }

    public function setType(?AccountType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        // set the owning side of the relation if necessary
        if ($user->getAccount() !== $this) {
            $user->setAccount($this);
        }

        $this->user = $user;

        return $this;
    }

}
