<?php

namespace App\Entity\Ecommerce\Snapshot;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Ecommerce\Order;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\Snapshot\UserDataSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserDataSnapshotRepository::class)
 */
class UserDataSnapshot implements EntityInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $accountTypeName;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userDataSnapshots")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $email;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $username;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $firstname;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $lastname;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\OneToOne(targetEntity=Order::class, mappedBy="userDataSnapshot", cascade={"persist", "remove"})
     */
    private Order $relatedOrder;

    /**
     * @ORM\OneToOne(targetEntity=AddressSnapshot::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $addressSnapshot;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountTypeName(): ?string
    {
        return $this->accountTypeName;
    }

    public function setAccountTypeName(string $accountTypeName): self
    {
        $this->accountTypeName = $accountTypeName;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    public function setRelatedOrder(Order $relatedOrder): self
    {
        // set the owning side of the relation if necessary
        if ($relatedOrder->getUserDataSnapshot() !== $this) {
            $relatedOrder->setUserDataSnapshot($this);
        }

        $this->relatedOrder = $relatedOrder;

        return $this;
    }

    public function getAddressSnapshot(): ?AddressSnapshot
    {
        return $this->addressSnapshot;
    }

    public function setAddressSnapshot(AddressSnapshot $addressSnapshot): self
    {
        // set the owning side of the relation if necessary
        if ($addressSnapshot->getUser() !== $this) {
            $addressSnapshot->setUser($this);
        }

        $this->addressSnapshot = $addressSnapshot;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

}
