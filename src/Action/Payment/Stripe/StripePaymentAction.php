<?php

namespace App\Action\Payment\Stripe;

use App\Response\Base\BaseResponse;
use App\Response\Payment\Stripe\GetPaymentIntentToken;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Payment\PaymentService;
use App\Service\System\State\SystemStateService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Handles payment explicitly related to: {@link https://stripe.com/}
 */
class StripePaymentAction extends AbstractController
{
    public function __construct(
        private readonly FinancesHubService     $financesHubService,
        private readonly SystemStateService     $systemStateService,
        private readonly TranslatorInterface    $translator,
        private readonly ValidationService      $validationService,
        private readonly PaymentService         $paymentService,
        private readonly LoggerInterface        $logger,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * This relies on the {@see PaymentService::prepareFromRequestData()} which goes through the normal
     * order process costs calculation etc., thus the result prices and so on are going to be accurate.
     *
     * No order data etc. is getting persisted in process, the process is called only to obtain the information
     * necessary for creating payment intent token.
     *
     * Not creating order on this point because there is no way to detect on front if user cancelled the payment etc.
     * and creating such logic would be to time-wise expensive, thus this solution.
     *
     * It's highly unlikely that the prices would be inaccurate once user actually presses "buy".
     * The only situation where I can see this happening would be someone changing the "price per point",
     * which would result in showing user old price first and then charging with new one.
     *
     * There are however some mechanisms which catch the value from front, so what most likely WILL happen is:
     * - using the price from front (there is a tolerance to it so user cannot manipulate it too much),
     * - sending critical email with data that such case happened,
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     */
    #[Route("/payment/stripe/get-payment-intent-token", name: "payment.stripe.get_payment_intent_token", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function getPaymentIntentToken(Request $request): JsonResponse
    {
        try {
            $this->entityManager->beginTransaction();
            if ($this->systemStateService->isSystemDisabled()) {
                return BaseResponse::buildMaintenanceResponse($this->translator->trans('state.disabled.downForMaintenance'))->toJsonResponse();
            }

            $requestContent = $request->getContent();
            $requestData    = json_decode($requestContent, true);
            if (!$this->validationService->validateJson($requestContent)) {
                return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
            }

            $preparedOrderBag = $this->paymentService->prepareFromRequestData($requestData);
            $this->entityManager->rollback();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            $this->logger->critical($e, [
                "info"        => "Could not build the temporary order for setting Payment Intent data",
                "requestData" => $requestData ?? "Something crashed before variable was set",
            ]);

            return GetPaymentIntentToken::buildInternalServerErrorResponse()->toJsonResponse();
        }

        if (is_null($preparedOrderBag->getOrder())) {
            $this->logger->critical("Order is not set on the temporary order used to get Payment Intent data");
            return GetPaymentIntentToken::buildInternalServerErrorResponse()->toJsonResponse();
        }

        if (is_null($preparedOrderBag->getOrder()->getCost())) {
            $this->logger->critical("Order costs are not set on the temporary order used to get Payment Intent data");
            return GetPaymentIntentToken::buildInternalServerErrorResponse()->toJsonResponse();
        }

        $token = $this->financesHubService->getStripePaymentIntentToken(
            $preparedOrderBag->getOrder()->getCost()->getTotalWithTax(),
            $preparedOrderBag->getOrder()->getTargetCurrencyCode()
        );

        $response = GetPaymentIntentToken::buildOkResponse();
        $response->setIntentToken($token);

        return $response->toJsonResponse();
    }

}
