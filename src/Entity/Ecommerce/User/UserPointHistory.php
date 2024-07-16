<?php

namespace App\Entity\Ecommerce\User;

use App\Entity\Ecommerce\Order;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\User\UserPointHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=UserPointHistoryRepository::class)
 */
class UserPointHistory
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $amountBefore;

    /**
     * @ORM\Column(type="integer")
     */
    private int $amountNow;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $type;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class)
     * @ORM\JoinColumn(nullable=true)
     */
    #[Ignore]
    private ?Order $relatedOrder = null;

    /**
     * @ORM\Column(type="json")
     */
    private array $extraData = [];

    /**
     * Used for example for debugging
     *
     * @ORM\Column(type="json")
     */
    #[Ignore]
    private array $internalData = [];

    /**
     * @ORM\Column(type="text")
     */
    private string $information;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Ignore]
    private ?string $pointShopProductSnapshot = null;

    /**
     * This is used only on front.
     *
     * The null value is valid, because for example "points returned on failed execution" does not have any related
     * point shop product, yet it's still valid point history entry.
     */
    private ?string $productSnapshotIdentifier = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    #[Ignore]
    private User $user;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getAmountBefore(): int
    {
        return $this->amountBefore;
    }

    /**
     * @param int $amountBefore
     */
    public function setAmountBefore(int $amountBefore): void
    {
        $this->amountBefore = $amountBefore;
    }

    /**
     * @return int
     */
    public function getAmountNow(): int
    {
        return $this->amountNow;
    }

    /**
     * @param int $amountNow
     */
    public function setAmountNow(int $amountNow): void
    {
        $this->amountNow = $amountNow;
    }

    /**
     * Return points difference between the "amount before" and "amount now"
     *
     * @return int
     */
    public function getAbsAmountDiff(): int
    {
        return abs($this->getAmountBefore() - $this->getAmountNow());
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Order|null
     */
    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    /**
     * @param Order|null $relatedOrder
     */
    public function setRelatedOrder(?Order $relatedOrder): void
    {
        $this->relatedOrder = $relatedOrder;
    }

    /**
     * @return string
     */
    public function getInformation(): string
    {
        return $this->information;
    }

    /**
     * @param string $information
     */
    public function setInformation(string $information): void
    {
        $this->information = $information;
    }

    /**
     * @return string|null
     */
    public function getPointShopProductSnapshot(): ?string
    {
        return $this->pointShopProductSnapshot;
    }

    /**
     * @param string|null $pointShopProductSnapshot
     */
    public function setPointShopProductSnapshot(?string $pointShopProductSnapshot): void
    {
        $this->pointShopProductSnapshot = $pointShopProductSnapshot;
    }

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

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
    }

    /**
     * @return array
     */
    public function getInternalData(): array
    {
        return $this->internalData;
    }

    public function setInternalData(array $internalData): void
    {
        $this->internalData = $internalData;
    }

    public function getProductSnapshotIdentifier(): ?string
    {
        return $this->productSnapshotIdentifier;
    }

    public function setProductSnapshotIdentifier(?string $productSnapshotIdentifier): void
    {
        $this->productSnapshotIdentifier = $productSnapshotIdentifier;
    }

}
