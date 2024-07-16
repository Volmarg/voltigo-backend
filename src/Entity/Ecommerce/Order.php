<?php

namespace App\Entity\Ecommerce;

use App\Entity\Ecommerce\Cost\Cost;
use App\Entity\Ecommerce\Snapshot\Product\OrderPointProductSnapshot;
use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use App\Entity\Ecommerce\Snapshot\UserDataSnapshot;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\OrderRepository;
use App\Traits\Validation\BelongsToUserTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use LogicException;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order implements EntityInterface
{
    use BelongsToUserTrait;
    use CreatedAndModifiedTrait;

    /**
     * This status represents an order where the data has to be initially saved in DB and then processed further
     * based on certain things, like for example SDK PayPal triggering payment fully on front.
     */
    public const STATUS_PREPARED = "PREPARED";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?User $user;

    /**
     * @ORM\OneToOne(targetEntity=UserDataSnapshot::class, inversedBy="relatedOrder", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private UserDataSnapshot $userDataSnapshot;

    /**
     * @ORM\OneToOne(targetEntity=PaymentProcessData::class, inversedBy="relatedOrder", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private PaymentProcessData $paymentProcessData;

    /**
     * @ORM\OneToOne(targetEntity=Cost::class, mappedBy="relatedOrder", cascade={"persist", "remove"})
     */
    private Cost $cost;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $status;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $activated = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $mailed = false;

    /**
     * @ORM\Column(type="string")
     */
    private string $targetCurrencyCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $transferredToFinancesHub = false;

    /**
     * @ORM\OneToMany(targetEntity=OrderProductSnapshot::class, mappedBy="order", cascade={"persist", "remove"}))
     */
    private $productSnapshots;

    public function __construct()
    {
        $this->initCreatedAndModified();
        $this->productSnapshots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCost(): ?Cost
    {
        return $this->cost;
    }

    public function setCost(Cost $cost): self
    {
        // set the owning side of the relation if necessary
        if ($cost->getRelatedOrder() !== $this) {
            $cost->setRelatedOrder($this);
        }

        $this->cost = $cost;

        return $this;
    }

    /**
     * @return UserDataSnapshot
     */
    public function getUserDataSnapshot(): UserDataSnapshot
    {
        return $this->userDataSnapshot;
    }

    /**
     * @param UserDataSnapshot $userDataSnapshot
     */
    public function setUserDataSnapshot(UserDataSnapshot $userDataSnapshot): void
    {
        $this->userDataSnapshot = $userDataSnapshot;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    public function getMailed(): ?bool
    {
        return $this->mailed;
    }

    public function setMailed(bool $mailed): self
    {
        $this->mailed = $mailed;

        return $this;
    }

    /**
     * @return Collection<int, OrderProductSnapshot>
     */
    public function getProductSnapshots(): Collection
    {
        return $this->productSnapshots;
    }

    public function addProductSnapshot(OrderProductSnapshot $productSnapshot): self
    {
        if (!$this->productSnapshots->contains($productSnapshot)) {
            $this->productSnapshots[] = $productSnapshot;
            $productSnapshot->setOrder($this);
        }

        return $this;
    }

    public function removeProductSnapshot(OrderProductSnapshot $productSnapshot): self
    {
        if ($this->productSnapshots->removeElement($productSnapshot)) {
            // set the owning side to null (unless already changed)
            if ($productSnapshot->getOrder() === $this) {
                $productSnapshot->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * If the order has snapshot of type {@see OrderPointProductSnapshot} then it will attempt to return the amount
     * of points that user is expected to get after the order is finished with success
     * @return int
     */
    public function getExpectedPoints(): int
    {
        if ($this->getProductSnapshots()->count() > 1) {
            throw new LogicException("
                This order got multiple products related. Not supporting multiple products per order.
                It's unknown how to handle this order! Order id: {$this->getId()}.
            ");
        }

        if ($this->getProductSnapshots()->isEmpty()) {
            throw new LogicException("
                This order got no associated products. There is some severe issue here!
                It's unknown how to handle this order! Order id: {$this->getId()}.
            ");
        }

        if (!($this->getProductSnapshots()->first() instanceof OrderPointProductSnapshot)) {
            $msg = "
                This snapshot is not of type: "
                   . OrderPointProductSnapshot::class
                   . "Order id: {$this->getId()}";
            throw new LogicException($msg);
        }

        return $this->getProductSnapshots()->first()->getAmount();
    }

    /**
     * @return string
     */
    public function getTargetCurrencyCode(): string
    {
        return $this->targetCurrencyCode;
    }

    /**
     * @param string $targetCurrencyCode
     */
    public function setTargetCurrencyCode(string $targetCurrencyCode): void
    {
        $this->targetCurrencyCode = $targetCurrencyCode;
    }

    public function getPaymentProcessData(): PaymentProcessData
    {
        return $this->paymentProcessData;
    }

    public function setPaymentProcessData(PaymentProcessData $paymentProcessData): void
    {
        $this->paymentProcessData = $paymentProcessData;
    }

    /**
     * @return bool
     */
    public function isPreparedState(): bool
    {
        return ($this->getStatus() === self::STATUS_PREPARED);
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return ($this->getStatus() === PaymentStatusEnum::CANCELLED->name);
    }

    /**
     * @return bool
     */
    public function isTransferredToFinancesHub(): bool
    {
        return $this->transferredToFinancesHub;
    }

    /**
     * @param bool $transferredToFinancesHub
     */
    public function setTransferredToFinancesHub(bool $transferredToFinancesHub): void
    {
        $this->transferredToFinancesHub = $transferredToFinancesHub;
    }

}
