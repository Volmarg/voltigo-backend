<?php

namespace App\DTO\Order;

class OrderDataDto
{
    private int $id;
    private string $status;
    private string $created;
    private float $totalWithTax;
    private float $totalWithoutTax;
    private array $productsData = [];
    private string $targetCurrencyCode;

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
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated(string $created): void
    {
        $this->created = $created;
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
     * @return array
     */
    public function getProductsData(): array
    {
        return $this->productsData;
    }

    /**
     * @param array $productsData
     */
    public function setProductsData(array $productsData): void
    {
        $this->productsData = $productsData;
    }

    /**
     * @param string $productData
     */
    public function addProductData(string $productData): void
    {
        $this->productsData[] = $productData;
    }

    public function getTargetCurrencyCode(): string
    {
        return $this->targetCurrencyCode;
    }

    public function setTargetCurrencyCode(string $targetCurrencyCode): void
    {
        $this->targetCurrencyCode = $targetCurrencyCode;
    }

}