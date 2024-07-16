<?php

namespace App\Service\Payment;

use App\Entity\Ecommerce\Product\Product;
use App\Exception\Payment\PriceCalculationException;
use App\Service\Api\FinancesHub\FinancesHubService;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Handles calculating / validating (etc.) prices of product
 */
class PriceCalculationService
{
    public function __construct(
      private readonly FinancesHubService $financesHubService
    ){}

    /**
     * The thing is that user might see 2.34 on front, then if something goes bad on backend he might get
     * charged 2.45 and some ppl would go insane on that.
     *
     * Also, they payment might fail if user actually got only 2.34 on account.
     * There should theoretically be no calculation difference between what user got on front and what comes from
     * financesHub, yet the tolerance is added to be in favour for user.
     *
     * Different should never be high, because it's pretty much like impossible that the exchange rate differs so
     * much after few minutes.
     *
     * If the exchange rate difference is however higher than given percentage amount it can be expected that someone
     * tries to manipulate price on front - so this also serves as "catch" for such cases
     *
     * {@see PaymentService::EXCHANGE_RATE_TOLERANCE_PERCENTAGE}, based on that the billed price is taken
     * on CUSTOMER FAVOUR, meaning that if the price coming from front and is within mentioned tolerance,
     * then it favours the user.
     */
    private const PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE = 2;

    /**
     * See for example:
     * - {@link https://www.paypal.com/dm/webapps/mpp/business-support/pricing}
     * - {@link https://stripe.com/en-pl/pricing}
     *
     * Each tool has its own fee calculation logic. The problem with that is that there are bunch of methods to cover:
     * - hardcoded-value + % from total,
     * - hardcoded-value + % from value up to given amount (higher value less % etc),
     *
     * It's just way too much to handle this way. Instead, came up with other lazy solution, which is just taking
     * some extra high-enough percentage value which will cover up the expenses for all cases.
     *
     * Yes, it could be done properly but not worth it now as it would need to:
     * - check card type,
     * - check transaction type,
     * - building mapping of % & hardcoded values per tool,
     * - setting formulas per tool,
     * - etc.
     */
    public const PAYMENT_TOOL_FEE_PERCENTAGE = 20;

    /**
     * @param Product $productEntity
     * @param string  $targetCurrency
     * @param float   $calculatedUnitPrice - coming for example from frontend
     * @param float   $calculatedUnitPriceWithTax
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws PriceCalculationException
     */
    public function validateCalculatedPrice(
        Product $productEntity,
        string  $targetCurrency,
        float   $calculatedUnitPrice,
        float   $calculatedUnitPriceWithTax
    ): void  {
        $expectedBaseUnitPrice        = $this->getBilledBasePrice($productEntity, $targetCurrency);
        $expectedBaseUnitPriceWithTax = $this->getBilledBasePriceWithTax($productEntity, $targetCurrency);

        $priceWithoutTaxPercentageDiff = abs(($expectedBaseUnitPrice - $calculatedUnitPrice) / $calculatedUnitPrice) * 100;
        $priceWithTaxPercentageDiff    = abs(($expectedBaseUnitPriceWithTax - $calculatedUnitPriceWithTax) / $calculatedUnitPriceWithTax) * 100;

        if ($priceWithoutTaxPercentageDiff > self::PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE) {
            $message = "
                The provided calculated price is not within the allowed tolerance percentage.
                Maybe someone manipulated the data?
                - Got base unit price: {$calculatedUnitPrice},
                - Calculated price on backend: {$expectedBaseUnitPrice},
                - Diff percentage: {$priceWithoutTaxPercentageDiff}
                - Allowed tolerance percentage: " . self::PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE;
            throw new PriceCalculationException($message);
        }

        if ($priceWithTaxPercentageDiff > self::PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE) {
            $message = "
                The provided calculated price is not within the allowed tolerance percentage.
                Maybe someone manipulated the data?
                - Got base unit price: {$calculatedUnitPriceWithTax},
                - Calculated price on backend: {$expectedBaseUnitPriceWithTax},
                - Diff percentage: {$priceWithTaxPercentageDiff}
                - Allowed tolerance percentage: " . self::PRICE_DIFFERENCE_TOLERANCE_PERCENTAGE;
            throw new PriceCalculationException($message);
        }
    }

    /**
     * This is going to be the base price of the product (without tax)
     *
     * @param Product $productEntity
     * @param string  $targetCurrency
     *
     * @return float
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getBilledBasePrice(Product $productEntity, string $targetCurrency): float
    {
        $usedBasePrice = $productEntity->getPrice();
        if ($productEntity->getBaseCurrencyCode() !== $targetCurrency) {
            $exchangeRate  = $this->financesHubService->getExchangeRate($productEntity->getBaseCurrencyCode(), $targetCurrency);
            $usedBasePrice = $usedBasePrice * $exchangeRate;
        }

        return round($usedBasePrice, 2);
    }

    /**
     * This is going to be the base price with product increased by the tax percentage,
     * so this is what customer will actually pay
     *
     * @param Product $productEntity
     * @param string  $targetCurrency
     *
     * @return float
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function getBilledBasePriceWithTax(Product $productEntity, string $targetCurrency): float
    {
        $basePrice = $this->getBilledBasePrice($productEntity, $targetCurrency);
        return $this->increaseByTax($basePrice);
    }

    /**
     * Takes the price and increases it by system-wide tax
     *
     * @param float $price
     *
     * @return float
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function increaseByTax(float $price): float
    {
        $systemTaxPercentage = $this->financesHubService->getTaxPercentage();
        $taxAddedPrice       = ($systemTaxPercentage * $price) / 100;
        $priceWithTax        = $price + $taxAddedPrice;

        return round($priceWithTax, 2);
    }
}