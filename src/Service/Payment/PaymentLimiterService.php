<?php

namespace App\Service\Payment;

use App\Entity\Ecommerce\Product\Product;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Security\JwtAuthenticationService;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Handles limiting the user payments
 */
class PaymentLimiterService
{
    public const MAX_ALLOWED_PAYMENT_UNITS = 50;
    public const MAX_ALLOWED_PAYMENT_CURRENCY = "EUR";

    public function __construct(
        private readonly LoggerInterface          $logger,
        private readonly FinancesHubService       $financesHubService,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ){}

    /**
     * Check if given payment can be made:
     * - check if single payment limitation has been reached or not.
     *
     * @param float   $paidAmount
     * @param string  $targetCurrency
     * @param Product $product
     *
     * @return bool
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function canBuy(float $paidAmount, string $targetCurrency, Product $product): bool
    {
        $exchangeRate = 1;
        if (strtolower($targetCurrency) !== strtolower(self::MAX_ALLOWED_PAYMENT_CURRENCY)) {
            $exchangeRate = $this->financesHubService->getExchangeRate($targetCurrency, self::MAX_ALLOWED_PAYMENT_CURRENCY);
        }

        $canBuy = (($paidAmount * $exchangeRate) <= self::MAX_ALLOWED_PAYMENT_UNITS);
        if (!$canBuy) {
            $user = $this->jwtAuthenticationService->getUserFromRequest();
            $this->logger->critical("Someone tried to pay above allowed max payment", [
                "info" => [
                    "Maybe some product price is higher than limitation",
                    "Someone manipulated page data"
                ],
                "userId"     => $user->getId(),
                "productId"  => $product->getId(),
                "maxAllowed" => [
                    "unit"     => self::MAX_ALLOWED_PAYMENT_UNITS,
                    "currency" => self::MAX_ALLOWED_PAYMENT_CURRENCY,
                ],
            ]);
        }

        return $canBuy;
    }
}