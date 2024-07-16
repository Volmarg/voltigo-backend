<?php

namespace App\Entity\Ecommerce\Account;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Entity\Traits\IsActiveTrait;
use App\Entity\Traits\SoftDeletableTrait;
use App\Repository\Ecommerce\Account\AccountTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Contains information about account type
 *
 * @ORM\Entity(repositoryClass=AccountTypeRepository::class)
 */
class AccountType implements EntityInterface
{
    use CreatedAndModifiedTrait;
    use SoftDeletableTrait;
    use IsActiveTrait;

    const TYPE_FREE                = "FREE";
    const TYPE_MEMBERSHIP_STANDARD = "MEMBERSHIP_STANDARD";

    const FIELD_NAME = "name";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $daysDuration;

    /**
     * @var ArrayCollection<int, Account>
     * @ORM\OneToMany(targetEntity=Account::class, mappedBy="type")
     */
    private $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDaysDuration(): ?int
    {
        return $this->daysDuration;
    }

    public function setDaysDuration(?int $daysDuration): self
    {
        $this->daysDuration = $daysDuration;

        return $this;
    }

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): self
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts[] = $account;
            $account->setType($this);
        }

        return $this;
    }

    public function removeAccount(Account $account): self
    {
        if ($this->accounts->removeElement($account)) {
            // set the owning side to null (unless already changed)
            if ($account->getType() === $this) {
                $account->setType(null);
            }
        }

        return $this;
    }

}
