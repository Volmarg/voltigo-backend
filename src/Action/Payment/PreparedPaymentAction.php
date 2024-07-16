<?php

namespace App\Action\Payment;

use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\PaymentProcessData;
use App\Enum\Payment\PaymentProcessStateEnum;
use App\Exception\Payment\PaymentPrepareException;
use App\Exception\Security\OtherUserResourceAccessException;
use App\Response\Base\BaseResponse;
use App\Response\Payment\PrepareResponse;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Logger\LoggerService;
use App\Service\Payment\PaymentService;
use App\Service\Payment\PaymentTransferService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\System\State\SystemStateService;
use App\Service\Validation\ValidationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Logic in here should be used only for payment services that handle the payments fully on front.
 * This logic in here consist of 2 crucial steps:
 * - prepare order, snapshots etc. but do not send it to {@see FinancesHubService},
 * - send the finished order data to {@see FinancesHubService},
 * - handle different order states (based on handling on front),
 *
 * With that it's possible to just save the data initially, and then eventually recreate it if needed.
 * Keep in mind that it's proper that {@see PaymentProcessData::getPaymentToolData()} is missing the paymentId
 * as the payment tool might result in calling hook "onError" like PayPal does, without even reaching the paymentId fetching
 */
class PreparedPaymentAction extends AbstractController
{
    public const MAX_HOURS_OFFSET_TO_FINISH = 4;

    public function __construct(
        private readonly ValidationService        $validationService,
        private readonly PaymentService           $paymentService,
        private readonly LoggerService            $loggerService,
        private readonly EntityManagerInterface   $entityManager,
        private readonly TranslatorInterface      $translator,
        private readonly SystemStateService       $systemStateService,
        private readonly FinancesHubService       $financesHubService,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly PaymentTransferService   $paymentTransferService
    ) {

    }

    /**
     * Triggers the whole process of preparing the payment without actually triggering it
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     */
    #[Route("/payment/prepared/prepare", name: "payment.prepared.prepare", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function prepareOrder(Request $request): JsonResponse
    {
        $this->entityManager->beginTransaction();

        $response = PrepareResponse::buildOkResponse();
        try {
            if ($this->systemStateService->isSystemDisabled()) {
                return PrepareResponse::buildMaintenanceResponse($this->translator->trans('state.disabled.downForMaintenance'))->toJsonResponse();
            }

            $requestContent = $request->getContent();
            $requestData    = json_decode($requestContent, true);
            if (!$this->validationService->validateJson($requestContent)) {
                return PrepareResponse::buildInvalidJsonResponse()->toJsonResponse();
            }

            $preparedOrderBag = $this->paymentService->prepareFromRequestData($requestData);
            if ($preparedOrderBag->isResponseSet()) {
                return $preparedOrderBag->getResponse();
            }

            if (is_null($preparedOrderBag->getOrder())) {
                throw new LogicException("It's expected to have order set on this step, yet it's not present!");
            }

            $preparedOrderBag->getOrder()->setStatus(Order::STATUS_PREPARED);
            $this->entityManager->persist($preparedOrderBag->getOrder());
            $this->entityManager->flush();

            $response->setOrderId($preparedOrderBag->getOrder()->getId());

            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();

            $loggerData = [];
            if ($e instanceof PaymentPrepareException) {
                $loggerData['paymentProcessState'] = $e->getPaymentProcessState();
            }

            $response = PrepareResponse::buildInternalServerErrorResponse();

            $loggerData["info"] = "Failed preparing order";
            $this->loggerService->logException($e, $loggerData);
        }

        return $response->toJsonResponse();
    }

    /**
     * Finishes up the order prepared in {@see self::prepareOrder()}
     *
     * @param Order $order
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     * @throws OtherUserResourceAccessException
     */
    #[Route("/payment/prepared/finish/{id}", name: "payment.prepared.finish", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function finishOrder(Order $order): JsonResponse
    {
        $order->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());
        $this->entityManager->beginTransaction();

        $response = BaseResponse::buildOkResponse();
        try {
            $this->validatePreparedOrder($order);
            if ($this->systemStateService->isSystemDisabled()) {
                return BaseResponse::buildMaintenanceResponse($this->translator->trans('state.disabled.downForMaintenance'))->toJsonResponse();
            }

            if ($order->getProductSnapshots()->count() > 1) {
                throw new LogicException("This order has more than one related products. This is not allowed!");
            }

            $productSnapshot     = $order->getProductSnapshots()->first();
            $productFromSnapshot = $productSnapshot->getProduct();
            if (is_null($productFromSnapshot)) {
                throw new LogicException("Original product for ProductSnapshot of id: {$productSnapshot->getId()}, does not exist");
            }

            $paymentBag  = $this->paymentTransferService->buildPaymentBag($order, $productSnapshot, $productFromSnapshot);
            $transaction = $this->paymentTransferService->buildTransaction($productFromSnapshot, $productSnapshot, $paymentBag, $order);

            $paymentState = PaymentProcessStateEnum::REAL_PAYMENT_BEGAN_DATA_SENT_TO_FINANCES_HUB;
            $this->financesHubService->insertTransaction($transaction);

            $paymentState = PaymentProcessStateEnum::GOT_RESPONSE_FROM_FINANCES_HUB;
            $message      = $this->translator->trans('payment.message.paymentDone');

            // PENDING because the payment needs to be sent to finances-hub and then validated toward used payment tool
            $order->setStatus(PaymentStatusEnum::PENDING->name);
            $order->setTransferredToFinancesHub(true);

            $this->entityManager->persist($order->getPaymentProcessData());
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();

            if (!isset($paymentState)) {
                $paymentState = null;
            }

            if ($e instanceof PaymentPrepareException) {
                $paymentState = $e->getPaymentProcessState();
            }

            $message  = $this->paymentService->getPaymentStateExceptionMessage($paymentState);
            $response = BaseResponse::buildInternalServerErrorResponse();

            $this->loggerService->logException($e, [
                "info"         => "Failed finishing order: {$order->getId()}!",
                "paymentState" => $paymentState?->name,
            ]);
        }

        $response->setMessage($message);
        return $response->toJsonResponse();
    }

    /**
     * Takes the data set from request and updates the payment tool data.
     * This method MUST avoid changing the order status. For status changes use:
     * - {@see self::finishOrder()}
     * - {@see self::cancelOrder()}
     * - {@see self::handleError()}
     *
     * @param Order   $order
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws OtherUserResourceAccessException
     */
    #[Route("/payment/prepared/update-payment-tool-data/{id}", name: "payment.prepared.update_payment_tool_data", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function updatePaymentToolData(Order $order, Request $request): JsonResponse
    {
        $order->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        try {
            $this->validatePreparedOrder($order);

            $response       = BaseResponse::buildOkResponse();
            $requestContent = $request->getContent();
            $requestData    = json_decode($requestContent, true);
            if (!$this->validationService->validateJson($requestContent)) {
                return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
            }

            $toolDataFromRequest = $requestData['paymentToolData'] ?? [];
            if (empty($toolDataFromRequest)) {
                throw new LogicException("No payment tool data was provided for update.");
            }
            $currentPaymentToolData = $order->getPaymentProcessData()->getPaymentToolData();
            $updatedToolData        = array_merge($currentPaymentToolData, $toolDataFromRequest);

            $order->getPaymentProcessData()->setPaymentToolData($updatedToolData);

            $this->entityManager->persist($order->getPaymentProcessData());
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e, [
                "info" => "Failed updating payment tool data for order: {$order->getId()}!"
            ]);
            $response = BaseResponse::buildInternalServerErrorResponse();
        }

        return $response->toJsonResponse();
    }

    /**
     * Triggered when the payment gets cancelled
     *
     * @param Order $order
     *
     * @return JsonResponse
     *
     * @throws OtherUserResourceAccessException
     */
    #[Route("/payment/prepared/cancel/{id}", name: "payment.prepared.cancel", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function cancelOrder(Order $order): JsonResponse
    {
        $order->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        try {
            /**
             * Had case where payment was done on przelewy24, yet there was popup to close, and it triggered
             * the "onCancel" which is ofc. a problem inside the used PayPal plugin
             */
            if (!$order->isPreparedState()) {
                return BaseResponse::buildOkResponse()->toJsonResponse();
            }

            $message = $this->translator->trans('payment.message.cancelled');
            $response = BaseResponse::buildOkResponse($message);

            $this->validatePreparedOrder($order);
            $order->setStatus(PaymentStatusEnum::CANCELLED->name);
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e, [
                "info" => "Failed cancelling order {$order->getId()}. Also failed updating the order status!"
            ]);
            $response = BaseResponse::buildInternalServerErrorResponse();
        }

        return $response->toJsonResponse();
    }

    /**
     * Triggered when payment error occurs.
     *
     * @param Order   $order
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws OtherUserResourceAccessException
     */
    #[Route("/payment/prepared/handle-error/{id}", name: "payment.prepared.handle_error", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function handleError(Order $order, Request $request): JsonResponse
    {
        $order->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        try {
            // that's correct because some tools like "PayPal" triggers this when payment window on front gets closed - even on error
            if ($order->isCancelled()) {
                return (BaseResponse::buildOkResponse())->toJsonResponse();
            }

            $this->validatePreparedOrder($order);

            $message = $this->translator->trans('payment.message.paymentError');
            $response = BaseResponse::buildBadRequestErrorResponse($message);

            $requestContent = $request->getContent();
            $requestData    = json_decode($requestContent, true);
            if (!$this->validationService->validateJson($requestContent)) {
                return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
            }

            $toolDataFromRequest = $requestData['paymentToolData'] ?? [];
            if (empty($toolDataFromRequest)) {
                throw new LogicException("No payment tool data was provided for update.");
            }
            $currentPaymentToolData = $order->getPaymentProcessData()->getPaymentToolData();
            $updatedToolData        = array_merge($currentPaymentToolData, [
                'errorData' => $toolDataFromRequest,
            ]);

            $order->getPaymentProcessData()->setPaymentToolData($updatedToolData);
            $order->setStatus(PaymentStatusEnum::ERROR->name);

            $this->entityManager->persist($order->getPaymentProcessData());
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e, [
                "info" => "Failed handling error for order {$order->getId()}. Also failed updating the order status!"
            ]);
            $response = BaseResponse::buildInternalServerErrorResponse();
        }

        return $response->toJsonResponse();
    }

    /**
     * That's a safety catch.
     *
     * Normally the {@see self::finishOrder()} is called shortly after {@see self::prepareOrder()}, it could however
     * happen that someone would try to use the logic for closing older orders etc. which could cause a problem as the
     * opened order would have invalid data etc.
     *
     * The used offset should be both high enough to let user finish the process but also low enough to prevent mentioned issue.
     * The thing is that {@see self::prepareOrder()} will be called when user clicks on payment button (at this step order must be registered),
     * yet the {@see self::finishOrder()} is called once the payment tool responds. This can lead to situation where
     * user opens the payment form, comes back in 30min or longer and then clicks "finish payment", or will keep
     * waiting for payment confirmation from bank (for example "przelewy24" does so).
     *
     * The same logic applies for other methods:
     * - {@see self::updatePaymentToolData()}}
     * - {@see self::cancelOrder()}}
     *
     * Other issue could be that someone could try and mess up someone else order.
     *
     * @param Order $order
     */
    private function validatePreparedOrder(Order $order): void
    {
        $offset = "+" . self::MAX_HOURS_OFFSET_TO_FINISH . " HOUR";
        $now    = new DateTime();
        $maxOld = (clone $order->getCreated())->modify($offset);
        $user   = $this->jwtAuthenticationService->getUserFromRequest();
        if (is_null($order->getUser())) {
            throw new LogicException("Order id: {$order->getId()}. Order has no user related - not allowed. ");
        }

        if ($user->getId() !== $order->getUser()->getId()) {
            throw new LogicException("Order id: {$order->getId()}. User {$user->getId()} tried to update someone else order.");
        }

        if (!$order->isPreparedState()) {
            throw new LogicException("Order id: {$order->getId()}. Order with state {$order->getStatus()}, cannot be updated.");
        }

        /**
         * Idea behind this check is "what if for some reason someone will try to finish old order", and related data is
         * gone, no longer valid.
         *
         * This shouldn't really be any problem, but better be safe.
         */
        if ($maxOld->getTimestamp() < $now->getTimestamp()) {
            throw new LogicException("Order id: {$order->getId()}. Order is too old to be updated! Max offset is: {$offset}");
        }
    }
}