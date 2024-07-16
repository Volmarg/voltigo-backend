<?php

namespace App\Command\Finances;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Repository\Ecommerce\Product\PointProductRepository;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Payment\PriceCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Enum\PaymentToolEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

class UpdatePointProductPrices extends AbstractCommand
{
    const COMMAND_NAME = "finances:update-point-product-prices";

    private const PARAM_PRICE_PER_POINT = 'price-per-point';

    /**
     * Minimum price per point needed to fully cover costs (with own gain) for single full search.
     */
    private const MIN_PRICE_PER_POINT = 0.044; // PLN - grosz

    /**
     * @var float $pricePerPoint
     */
    private float $pricePerPoint;

    private ?float $eurExchangeRate = null;
    private ?float $usdExchangeRate = null;

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Updates all the point product prices by base 'price per point'");
        $this->addOption(self::PARAM_PRICE_PER_POINT, null, InputOption::VALUE_REQUIRED, "Price per single point");
        $this->addUsage('--price-per-point=' . self::MIN_PRICE_PER_POINT);
    }

    /**
     * @param FinancesHubService     $financesHubService
     * @param PointProductRepository $pointProductRepository
     * @param EntityManagerInterface $entityManager
     * @param ConfigLoader           $configLoader
     * @param KernelInterface        $kernel
     */
    public function __construct(
        private readonly FinancesHubService     $financesHubService,
        private readonly PointProductRepository $pointProductRepository,
        private readonly EntityManagerInterface $entityManager,
        ConfigLoader                            $configLoader,
        KernelInterface                         $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->pricePerPoint = $input->getOption(self::PARAM_PRICE_PER_POINT);
        if ($this->pricePerPoint < self::MIN_PRICE_PER_POINT) {
            throw new LogicException("Minimum price per point is: " . self::MIN_PRICE_PER_POINT . ", you've provided: {$this->pricePerPoint}");
        }
    }

    /**
     * Execute command logic
     *
     * @return int
     *
     * @throws GuzzleException
     */
    protected function executeLogic(): int
    {
        try {
            $listingData     = [];
            $maxInListing    = 5;
            $countForListing = 0;
            $currentTax      = $this->financesHubService->getTaxPercentage();

            $pointProducts = $this->pointProductRepository->findAllAccessible();
            foreach ($pointProducts as $pointProduct) {
                if (is_null($this->usdExchangeRate) && is_null($this->eurExchangeRate)) {
                    $fromCode              = strtolower($pointProduct->getBaseCurrencyCode());
                    $this->usdExchangeRate = $this->financesHubService->getExchangeRate($fromCode, "usd");
                    $this->eurExchangeRate = $this->financesHubService->getExchangeRate($fromCode, "eur");
                }

                $priceNetBefore  = $pointProduct->getPrice();
                $priceBrutBefore = $this->getPriceWithTax($priceNetBefore, $currentTax);

                $priceNetAfter  = $pointProduct->getAmount() * $this->pricePerPoint;
                $priceNetAfter  += (PriceCalculationService::PAYMENT_TOOL_FEE_PERCENTAGE / 100) * $priceNetAfter;
                $priceBrutAfter = $this->getPriceWithTax($priceNetAfter, $currentTax);

                $this->validatePrice($priceBrutAfter, $pointProduct);

                $pointProduct->setPrice($priceNetAfter);
                $this->entityManager->persist($pointProduct);

                if ($countForListing < $maxInListing) {
                    $listingData[] = $this->buildPriceChangeInfo(
                        $pointProduct,
                        $priceNetBefore,
                        $priceBrutBefore,
                        $priceNetAfter,
                        $priceBrutAfter
                    );
                }

                $countForListing++;
            }

            $this->printWarningsAndInfo($currentTax);
            $this->io->listing($listingData);

            if ($this->io->confirm("Do You accept this change?", false)) {
                $this->entityManager->flush();
                $this->io->success("Update successful");
            } else {
                $this->io->error("Not updating!");
            }
        } catch (Exception|TypeError $e) {
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param float $netPrice
     * @param float $currentTax
     *
     * @return float
     */
    private function getPriceWithTax(float $netPrice, float $currentTax): float
    {
        return $netPrice + ($netPrice * $currentTax / 100);
    }

    /**
     * @param float        $newPriceBrutto
     * @param PointProduct $pointProduct
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    private function validatePrice(float $newPriceBrutto, PointProduct $pointProduct): void
    {
        $transactionPaymentsData    = $this->financesHubService->getMinMaxTransactionData();
        $paypalCurrencyExchangeRate = $this->financesHubService->getExchangeRate("PLN", $transactionPaymentsData->getPaypalTransactionBaseCurrency());
        $stripeCurrencyExchangeRate = $this->financesHubService->getExchangeRate("PLN", $transactionPaymentsData->getStripeTransactionBaseCurrency());

        $paypalTransactionBrut = $paypalCurrencyExchangeRate * $newPriceBrutto;
        $stripeTransactionBrut = $stripeCurrencyExchangeRate * $newPriceBrutto;

        if (
                ($paypalTransactionBrut < $transactionPaymentsData->getPaypalMinTransaction())
            ||  ($paypalTransactionBrut > $transactionPaymentsData->getPaypalMaxTransaction())
        ) {
            $this->throwMinMaxPaymentViolated(
                PaymentToolEnum::PAYPAL->value,
                $newPriceBrutto,
                $paypalTransactionBrut,
                $transactionPaymentsData->getPaypalMinTransaction(),
                $transactionPaymentsData->getPaypalMaxTransaction(),
                $transactionPaymentsData->getPaypalTransactionBaseCurrency(),
                $pointProduct
            );
        }

        if (
                ($stripeTransactionBrut < $transactionPaymentsData->getStripeMinTransaction())
            ||  ($stripeTransactionBrut > $transactionPaymentsData->getStripeMaxTransaction())
        ) {
            $this->throwMinMaxPaymentViolated(
                PaymentToolEnum::STRIPE->value,
                $newPriceBrutto,
                $stripeTransactionBrut,
                $transactionPaymentsData->getStripeMinTransaction(),
                $transactionPaymentsData->getStripeMaxTransaction(),
                $transactionPaymentsData->getStripeTransactionBaseCurrency(),
                $pointProduct
            );
        }
    }

    /**
     * @param string       $toolName
     * @param float        $newPriceBrutto
     * @param float        $bruttoInToolBaseCurrency
     * @param float        $toolMinPayment
     * @param float        $toolMaxPayment
     * @param string       $toolCurrency
     * @param PointProduct $pointProduct
     *
     * @return never
     */
    private function throwMinMaxPaymentViolated(
        string                           $toolName,
        float                            $newPriceBrutto,
        float                            $bruttoInToolBaseCurrency,
        float                            $toolMinPayment,
        float                            $toolMaxPayment,
        string                           $toolCurrency,
        PointProduct                     $pointProduct,
    ): never
    {
        throw new LogicException(
            "[{$toolName}] Cannot set this price per point as payment would be violated."
            . "Brutto: {$newPriceBrutto}, "
            . "Brutto in base currency: {$bruttoInToolBaseCurrency} {$toolCurrency}, "
            . "Min allowed: {$toolMinPayment}, "
            . "Max allowed: {$toolMaxPayment}, "
            . "Violating point product: {$pointProduct->getId()}"
        );
    }

    /**
     * @param float $currentTax
     */
    private function printWarningsAndInfo(float $currentTax): void
    {
        $this->io->warning("Brut. prices DO INCLUDE payment tool & tax fee");
        $this->io->warning("Brut. prices DO NOT INCLUDE own gain fee");
        $this->io->warning("Prices are not perfectly equal to locally made calculation due to small differences in calculation,
where one calculation are based on tax from point then value is multiplied, and other takes whole value and then calculates tax.
There are few taxes calculated on the way so that might be the reason why values differ.
Doesn't have to be perfect in here, just adjust the provided per-point value till You get desired prices.");
        $this->io->warning("Remember! BRUTTO is what user MUST, pay for everything to be profitable.");

        $this->io->note("Current tax is: {$currentTax}%");
        $this->io->note("Current payment tool fee is: " . PriceCalculationService::PAYMENT_TOOL_FEE_PERCENTAGE . "%");
        $this->io->note("Example changes in prices");
    }


    /**
     * @param PointProduct $pointProduct
     * @param float|null   $priceNetBefore
     * @param float        $priceBrutBefore
     * @param float|int    $priceNetAfter
     * @param float        $priceBrutAfter
     *
     * @return string
     */
    private function buildPriceChangeInfo(
        PointProduct $pointProduct,
        ?float       $priceNetBefore,
        float        $priceBrutBefore,
        float|int    $priceNetAfter,
        float        $priceBrutAfter
    ): string {
        $singleListData = "{$pointProduct->getAmount()} Point/s."
                          . " Price, was: net: {$priceNetBefore} {$pointProduct->getBaseCurrencyCode()}"
                          . ", brut: {$priceBrutBefore} {$pointProduct->getBaseCurrencyCode()},"
                          . " (" . round(($priceBrutBefore * $this->usdExchangeRate), 3) . " USD"
                          . " / " . round(($priceBrutBefore * $this->eurExchangeRate), 3) . " EUR)"
                          . " is net: {$priceNetAfter} {$pointProduct->getBaseCurrencyCode()}"
                          . ", brut: {$priceBrutAfter} {$pointProduct->getBaseCurrencyCode()}"
                          . " (" . round(($priceBrutAfter * $this->usdExchangeRate), 3) . " USD"
                          . " / " . round(($priceBrutAfter * $this->eurExchangeRate), 3) . " EUR)";

        return $singleListData;
    }

}
