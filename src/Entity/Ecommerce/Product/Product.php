<?php

namespace App\Entity\Ecommerce\Product;

use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Entity\Traits\IsActiveTrait;
use App\Entity\Traits\SoftDeletableTrait;
use App\Repository\Ecommerce\Product\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;

/**
 * Represents the product available for purchase
 *
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @InheritanceType("JOINED") // must remain as annotation, else doctrine can't read php8.0 attribute, dunno why
 * @DiscriminatorColumn(name="discr", type="string")
 */
#[DiscriminatorMap([
    PointProduct::class,
])]
class Product implements EntityInterface
{
    public const CURRENCY_CODE = "PLN";

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
     * @ORM\Column(type="string", length=100)
     */
    private string $name;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\Column(type="float")
     */
    private float $price;

    /**
     * @ORM\Column(type="string", length=50)
     * @var string $baseCurrencyCode
     */
    private string $baseCurrencyCode = self::CURRENCY_CODE;

    /**
     * @ORM\OneToMany(targetEntity=OrderProductSnapshot::class, mappedBy="product")
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
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
            $productSnapshot->setProduct($this);
        }

        return $this;
    }

    public function removeProductSnapshot(OrderProductSnapshot $productSnapshot): self
    {
        if ($this->productSnapshots->removeElement($productSnapshot)) {
            // set the owning side to null (unless already changed)
            if ($productSnapshot->getProduct() === $this) {
                $productSnapshot->setProduct(null);
            }
        }

        return $this;
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

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}
