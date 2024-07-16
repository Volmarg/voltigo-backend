<?php

namespace App\Entity\Ecommerce\Cost;

use App\Entity\Ecommerce\Order;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\Cost\CostRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CostRepository::class)
 */
class Cost implements EntityInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="float")
     */
    private float $totalWithoutTax;

    /**
     * @ORM\Column(type="float")
     */
    private float $totalWithTax;

    /**
     * @ORM\Column(type="integer")
     */
    private int $usedTaxValue;

    /**
     * @ORM\OneToOne(targetEntity=Order::class, inversedBy="cost", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private Order $relatedOrder;

    /**
     * @ORM\Column(type="string", length=50)
     * @var string $currencyCode
     */
    private string $currencyCode;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getTotalWithoutTax(): float
    {
        return $this->totalWithoutTax;
    }

    /**
     * @param float $totalWithoutTax
     */
    public function setTotalWithoutTax(float $totalWithoutTax): void
    {
        $this->totalWithoutTax = $totalWithoutTax;
    }

    /**
     * @return float
     */
    public function getTotalWithTax(): float
    {
        return $this->totalWithTax;
    }

    /**
     * @param float $totalWithTax
     */
    public function setTotalWithTax(float $totalWithTax): void
    {
        $this->totalWithTax = $totalWithTax;
    }

    /**
     * @return int
     */
    public function getUsedTaxValue(): int
    {
        return $this->usedTaxValue;
    }

    /**
     * @param int $usedTaxValue
     */
    public function setUsedTaxValue(int $usedTaxValue): void
    {
        $this->usedTaxValue = $usedTaxValue;
    }

    /**
     * @return Order
     */
    public function getRelatedOrder(): Order
    {
        return $this->relatedOrder;
    }

    /**
     * @param Order $relatedOrder
     */
    public function setRelatedOrder(Order $relatedOrder): void
    {
        $this->relatedOrder = $relatedOrder;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

}
