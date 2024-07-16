<?php

namespace App\Entity\Ecommerce;

use App\Repository\Ecommerce\PaymentProcessDataRepository;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentProcessDataRepository::class)
 * @ORM\Table(name="`payment_process_data`")
 */
class PaymentProcessData implements EntityInterface
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
    private float $targetCurrencyCalculatedUnitPrice;

    /**
     * @ORM\Column(type="float")
     */
    private float $targetCurrencyCalculatedUnitPriceWithTax;

    /**
     * @ORM\Column(type="json")
     */
    private array $paymentToolData = [];

    /**
     * @ORM\Column(type="string")
     */
    private string $paymentTool;

    /**
     * @ORM\OneToOne(targetEntity=Order::class, mappedBy="paymentProcessData", cascade={"persist", "remove"})
     */
    private Order $relatedOrder;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetCurrencyCalculatedUnitPrice(): float
    {
        return $this->targetCurrencyCalculatedUnitPrice;
    }

    public function setTargetCurrencyCalculatedUnitPrice(float $targetCurrencyCalculatedUnitPrice): void
    {
        $this->targetCurrencyCalculatedUnitPrice = $targetCurrencyCalculatedUnitPrice;
    }

    public function getTargetCurrencyCalculatedUnitPriceWithTax(): float
    {
        return $this->targetCurrencyCalculatedUnitPriceWithTax;
    }

    public function setTargetCurrencyCalculatedUnitPriceWithTax(float $targetCurrencyCalculatedUnitPriceWithTax): void
    {
        $this->targetCurrencyCalculatedUnitPriceWithTax = $targetCurrencyCalculatedUnitPriceWithTax;
    }

    public function getPaymentToolData(): array
    {
        return $this->paymentToolData;
    }

    public function setPaymentToolData(array $paymentToolData): void
    {
        $this->paymentToolData = $paymentToolData;
    }

    public function getPaymentTool(): string
    {
        return $this->paymentTool;
    }

    public function setPaymentTool(string $paymentTool): void
    {
        $this->paymentTool = $paymentTool;
    }

    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    public function setRelatedOrder(Order $relatedOrder): self
    {
        // set the owning side of the relation if necessary
        if ($relatedOrder->getPaymentProcessData() !== $this) {
            $relatedOrder->setPaymentProcessData($this);
        }

        $this->relatedOrder = $relatedOrder;

        return $this;
    }


}
