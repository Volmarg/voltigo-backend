<?php

namespace App\Entity\Ecommerce\Snapshot\Product;

use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\Product\Product;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\Snapshot\OrderProductSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;

/**
 * @ORM\Entity(repositoryClass=OrderProductSnapshotRepository::class)
 * @InheritanceType("JOINED") // must remain as annotation, else doctrine can't read php8.0 attribute, dunno why
 * @DiscriminatorColumn(name="discr", type="string")
 */
#[DiscriminatorMap([
    OrderPointProductSnapshot::class,
])]
class OrderProductSnapshot implements EntityInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $quantity;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $name;

    /**
     * @ORM\Column(type="float")
     */
    private float $price;

    /**
     * @ORM\Column(type="float")
     */
    private float $priceWithTax;

    /**
     * @ORM\Column(type="float")
     */
    private float $taxPercentage;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productSnapshots")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="productSnapshots")
     */
    private $order;

    /**
     * @ORM\Column(type="string", length=50)
     * @var string $baseCurrencyCode
     */
    private string $baseCurrencyCode = Product::CURRENCY_CODE;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
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

    /**
     * @return float
     */
    public function getPriceWithTax(): float
    {
        return $this->priceWithTax;
    }

    /**
     * @param float $priceWithTax
     */
    public function setPriceWithTax(float $priceWithTax): void
    {
        $this->priceWithTax = $priceWithTax;
    }

    /**
     * @return float
     */
    public function getTaxPercentage(): float
    {
        return $this->taxPercentage;
    }

    /**
     * @param float $taxPercentage
     */
    public function setTaxPercentage(float $taxPercentage): void
    {
        $this->taxPercentage = $taxPercentage;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getBaseCurrencyCode(): string
    {
        return $this->baseCurrencyCode;
    }

    /**
     * @param string $baseCurrencyCode
     */
    public function setBaseCurrencyCode(string $baseCurrencyCode): void
    {
        $this->baseCurrencyCode = $baseCurrencyCode;
    }

}
