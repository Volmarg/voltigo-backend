<?php

namespace App\Service\Payment;

use App\DTO\Order\PreparedOrderBagDto;
use App\DTO\Payment\PaymentProcessDataBagDto;
use App\DTO\Validation\ValidationResultDTO;
use App\Entity\Ecommerce\PaymentProcessData;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Entity\Security\User;
use App\Enum\Payment\PaymentProcessStateEnum;
use App\Exception\Payment\PaymentPrepareException;
use App\Exception\Payment\PriceCalculationException;
use App\Repository\Ecommerce\Product\ProductRepository;
use App\Response\Base\BaseResponse;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Order\OrderService;
use App\Service\Points\UserPointsLimiterService;
use App\Service\Security\JwtAuthenticationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Dto\Customer;
use FinancesHubBridge\Dto\Product;
use App\Entity\Ecommerce\Product\Product as ProductEntity;
use FinancesHubBridge\Dto\Transaction;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Handles getting / preparing data for transaction
 */
class PaymentService
{

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly PriceCalculationService  $priceCalculationService,
        private readonly FinancesHubService       $financesHubService,
        private readonly UserPointsLimiterService $userPointsLimiterService,
        private readonly TranslatorInterface      $translator,
        private readonly PaymentLimiterService    $paymentLimiterService,
        private readonly OrderService             $orderService,
        private readonly ProductRepository        $productRepository,
        private readonly EntityManagerInterface   $entityManager
    ){}

    /**
     * @param ProductEntity            $productEntity
     * @param PaymentProcessDataBagDto $paymentBag
     * @param User|null                $user
     *
     * @return Transaction
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws PriceCalculationException
     */
    public function createTransaction(ProductEntity $productEntity, PaymentProcessDataBagDto $paymentBag, ?User $user = null): Transaction
    {
        $user ??= $this->jwtAuthenticationService->getUserFromRequest();

        $transaction = new Transaction();
        $customer    = new Customer();
        $product     = new Product();

        $usedTaxPercentage = $this->financesHubService->getTaxPercentage();
        $this->priceCalculationService->validateCalculatedPrice(
            $productEntity,
            $paymentBag->getCurrencyCode(),
            $paymentBag->getCalculatedUnitPrice(),
            $paymentBag->getCalculatedUnitPriceWithTax()
        );

        $product->setId($productEntity->getId());
        $product->setCurrency($paymentBag->getCurrencyCode());
        $product->setQuantity($paymentBag->getQuantity());
        $product->setCost($paymentBag->getCalculatedUnitPrice());
        $product->setCostWithTax($paymentBag->getCalculatedUnitPriceWithTax());
        $product->setTaxPercentage($usedTaxPercentage);
        $product->setIncludeTax(true);
        $product->setName($productEntity->getName());

        $customer->setName("{$user->getFirstname()} {$user->getLastname()}");
        $customer->setCountry($user->getAddress()->getCountry()->value);
        $customer->setZip($user->getAddress()->getZip());
        $customer->setCity($user->getAddress()->getCity());
        $customer->setAddress("{$user->getAddress()->getStreet()} {$user->getAddress()->getHomeNumber()}");
        $customer->setUserId($user->getId());

        $transaction->setPaymentTool($paymentBag->getPaymentTool()->value);
        $transaction->setPaymentToolData($paymentBag->getPaymentToolData());
        $transaction->setProducts([$product]);
        $transaction->setCustomer($customer);

        return $transaction;
    }

    /**
     * Check if product can be bought at all
     *
     * @param Product|PointProduct     $product
     * @param PaymentProcessDataBagDto $paymentBag
     *
     * @return ValidationResultDTO
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     */
    public function validateBeforePayment(Product|PointProduct $product, PaymentProcessDataBagDto $paymentBag): ValidationResultDTO
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        $dto  = ValidationResultDTO::buildOkValidation();

        if (
                $product instanceof PointProduct
            &&  !$this->userPointsLimiterService->canBuyPoints($product->getAmount())
        ) {
            $msg = $this->translator->trans('points.message.cannotBuyPointsWillReachAboveMax', [
                '%bought_amount%'  => $product->getAmount(),
                '%points_amount%'  => UserPointsLimiterService::MAX_POINTS_PER_USER,
                '%current_points%' => $user->getPointsAmount(),
                '%pending_points%' => $user->getPendingPointsAmount(),
            ]);

            return ValidationResultDTO::buildInvalidValidation($msg);
        }

        $totalPaidAmount = $paymentBag->getCalculatedUnitPriceWithTax() * $paymentBag->getQuantity();
        $canBuy = $this->paymentLimiterService->canBuy(
            $totalPaidAmount,
            $paymentBag->getCurrencyCode(),
            $product
        );

        $exchangeRate = 1;
        if ($paymentBag->getCurrencyCode() !== PaymentLimiterService::MAX_ALLOWED_PAYMENT_CURRENCY) {
            $exchangeRate = $this->financesHubService->getExchangeRate($paymentBag->getCurrencyCode(), PaymentLimiterService::MAX_ALLOWED_PAYMENT_CURRENCY);
        }

        $totalPaidAmountInLimiterCurrency = round($totalPaidAmount * $exchangeRate, 2);
        if (!$canBuy) {
            $msg = $this->translator->trans('payment.message.cannotPayMaxPaymentAmountReached', [
                "%allowed_with_currency%"   => PaymentLimiterService::MAX_ALLOWED_PAYMENT_UNITS . " " . PaymentLimiterService::MAX_ALLOWED_PAYMENT_CURRENCY,
                "%user_payment_original%"   => "{$totalPaidAmount} {$paymentBag->getCurrencyCode()}",
                "%user_payment_calculated%" => "{$totalPaidAmountInLimiterCurrency} " . PaymentLimiterService::MAX_ALLOWED_PAYMENT_CURRENCY,
            ]);
            return ValidationResultDTO::buildInvalidValidation($msg);
        }

        return $dto;
    }

    /**
     * Will prepare all the necessary order data (transaction, snapshots etc.), but will not persist it (on purpose)
     *
     * @param array $requestData
     *
     * @return PreparedOrderBagDto
     * @throws GuzzleException
     * @throws PaymentPrepareException
     */
    public function prepareFromRequestData(array $requestData): PreparedOrderBagDto
    {
        $paymentState = PaymentProcessStateEnum::BEGINNING;

        try{
            $preparedBag        = new PreparedOrderBagDto();
            $paymentBag         = PaymentProcessDataBagDto::fromDataArray($requestData);
            $validationResponse = $this->validatePaymentBag($paymentBag);

            if (!empty($validationResponse)) {
                $preparedBag->setResponse($validationResponse);
                return $preparedBag;
            }

            $product = $this->productRepository->find($paymentBag->getProductId());
            if (empty($product)) {
                $preparedBag->setResponse(BaseResponse::buildBadRequestErrorResponse("No such product was found")->toJsonResponse());
                return $preparedBag;
            }

            $validationResult = $this->validateBeforePayment($product, $paymentBag);
            if (!$validationResult->isSuccess()) {
                $preparedBag->setResponse(BaseResponse::buildBadRequestErrorResponse($validationResult->getMessage())->toJsonResponse());
                return $preparedBag;
            }

            $transaction = $this->createTransaction($product, $paymentBag);
            $paymentState = PaymentProcessStateEnum::CREATED_FINANCES_HUB_TRANSACTION;

            $paymentDataEntity = $this->buildPaymentProcessEntity($paymentBag);

            $order = $this->orderService->saveOrderAndSnapshots($product, $transaction, $paymentBag, $paymentDataEntity);
            $paymentDataEntity->setRelatedOrder($order);

            $paymentState = PaymentProcessStateEnum::CREATED_SNAPSHOTS_AND_ORDER;

            $transaction->setOrderId($order->getId());
            $transaction->setExpectedPriceWithTax($order->getCost()->getTotalWithTax());
            $transaction->setExpectedPriceWithoutTax($order->getCost()->getTotalWithoutTax());

            $this->entityManager->persist($paymentDataEntity);
            $this->entityManager->flush();

            $preparedBag->setOrder($order);
            $preparedBag->setProduct($product);
            $preparedBag->setTransaction($transaction);
            $preparedBag->setPaymentProcessDataBagDto($paymentBag);
            $preparedBag->setPaymentProcessData($paymentDataEntity);
        } catch (Exception|TypeError $e) {
            $msg = "Original msg: {$e->getMessage()}, trace: {$e->getTraceAsString()}";
            $exc = new PaymentPrepareException($msg, $e->getCode());
            $exc->setPaymentProcessState($paymentState);

            throw $exc;
        }

        return $preparedBag;
    }

    /**
     * Check if the payment bag state is correct
     *
     * @param PaymentProcessDataBagDto $paymentBag
     *
     * @return JsonResponse|null
     */
    private function validatePaymentBag(PaymentProcessDataBagDto $paymentBag): ?JsonResponse
    {
        if (empty($paymentBag->getProductId())) {
            return BaseResponse::buildBadRequestErrorResponse("Product id is missing")->toJsonResponse();
        }

        if (empty($paymentBag->getQuantity())) {
            return BaseResponse::buildBadRequestErrorResponse("Invalid product quantity")->toJsonResponse();
        }

        if (empty($paymentBag->getCurrencyCode())) {
            return BaseResponse::buildBadRequestErrorResponse("Currency is missing")->toJsonResponse();
        }

        if (empty($paymentBag->getCalculatedUnitPrice())) {
            return BaseResponse::buildBadRequestErrorResponse("Calculated unit price")->toJsonResponse();
        }

        if (empty($paymentBag->getCalculatedUnitPriceWithTax())) {
            return BaseResponse::buildBadRequestErrorResponse("Calculated unit price with tax is missing")->toJsonResponse();
        }

        return null;
    }

    /**
     * @param PaymentProcessDataBagDto $paymentBag
     *
     * @return PaymentProcessData
     */
    public function buildPaymentProcessEntity(PaymentProcessDataBagDto $paymentBag): PaymentProcessData
    {
        $paymentDataEntity = new PaymentProcessData();
        $paymentDataEntity->setPaymentTool($paymentBag->getPaymentTool()->value);
        $paymentDataEntity->setPaymentToolData($paymentBag->getPaymentToolData());
        $paymentDataEntity->setTargetCurrencyCalculatedUnitPrice($paymentBag->getCalculatedUnitPrice());
        $paymentDataEntity->setTargetCurrencyCalculatedUnitPriceWithTax($paymentBag->getCalculatedUnitPriceWithTax());

        return $paymentDataEntity;
    }


    /**
     * Will return the error message for case when an exception occurs during whole payment process
     *
     * @param PaymentProcessStateEnum|null $paymentProcessStateEnum
     *
     * @return string
     */
    public function getPaymentStateExceptionMessage(?PaymentProcessStateEnum $paymentProcessStateEnum): string
    {
        $name = $paymentProcessStateEnum?->name ?? null;

        switch ($name) {
            case PaymentProcessStateEnum::REAL_PAYMENT_BEGAN_DATA_SENT_TO_FINANCES_HUB->name:
                return $this->translator->trans('payment.message.couldNotDeterminePaymentState');
            default:
                return $this->translator->trans('payment.message.couldNotHandleThePayment');
        }
    }

}