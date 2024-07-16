<?php

namespace App\Entity\Ecommerce;

use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Ecommerce\PointShopProductRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PointShopProductRepository::class)
 */
class PointShopProduct
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="float")
     */
    private float $cost;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $internalIdentifier;

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
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     */
    public function setCost(float $cost): void
    {
        $this->cost = $cost;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInternalIdentifier(): string
    {
        return $this->internalIdentifier;
    }

    /**
     * @param string $internalIdentifier
     */
    public function setInternalIdentifier(string $internalIdentifier): void
    {
        $this->internalIdentifier = $internalIdentifier;
    }

}
