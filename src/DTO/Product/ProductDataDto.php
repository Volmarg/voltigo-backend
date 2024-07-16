<?php

namespace App\DTO\Product;

class ProductDataDto
{
    private int $id;
    private string $name;
    private string $currencyCode;
    private float $priceWithoutTax;
    private float $priceWithTax;
    private string $description;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
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

    /**
     * @return float
     */
    public function getPriceWithoutTax(): float
    {
        return $this->priceWithoutTax;
    }

    /**
     * @param float $priceWithoutTax
     */
    public function setPriceWithoutTax(float $priceWithoutTax): void
    {
        $this->priceWithoutTax = $priceWithoutTax;
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