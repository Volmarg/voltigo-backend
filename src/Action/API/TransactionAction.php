<?php

namespace App\Action\API;

use App\DTO\Api\FinancesHub\TransactionDetailDTO;
use App\DTO\Internal\WebsocketNotificationDto;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use App\Repository\Ecommerce\OrderRepository;
use App\Response\Api\BaseApiResponse;
use App\Service\Messages\Notification\WebsocketNotificationService;
use App\Service\Transaction\TransactionService;
use App\Service\Validation\ValidationService;
use App\Service\Websocket\Endpoint\AuthenticatedUserWebsocketEndpoint;
use Exception;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Handling of transactions related logic toward/from external systems
 */
#[Route("/api/transaction", name: "api.transaction")]
class TransactionAction extends AbstractController
{

    public function __construct(
        private readonly ValidationService            $validationService,
        private readonly TransactionService           $transactionService,
        private readonly WebsocketNotificationService $websocketNotificationService,
        private readonly OrderRepository              $orderRepository,
        private readonly TranslatorInterface          $translator
    )
    {
    }

    /**
     * Receives external request which contains information about transaction made for order, depending on the
     * provided data different handlers will be triggered and if everything is fine then the points will get
     * granted to the user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ApiException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws Throwable
     */
    #[Route("/update-from-request", name: "handle.incoming", methods: Request::METHOD_POST)]
    public function updateFromRequest(Request $request): JsonResponse
    {
        if (!is_string($request->getContent())) {
            throw new ApiException("Request content is not a string. Got type: " . gettype($request->getContent()));
        }

        $dataArray = json_decode($request->getContent(), true);
        if (!$this->validationService->validateJson($request->getContent())) {
            return BaseApiResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        $transactionDetails = TransactionDetailDTO::fromArray($dataArray);
        $order              = $this->orderRepository->find($transactionDetails->getOrderId());
        if (is_null($order->getUser())) {
            throw new Exception("This order ({$order->getId()}, is not related to user!)");
        }

        $this->transactionService->handleTransaction($transactionDetails);
        if ($transactionDetails->isTransactionSuccessful()) {
            $notification = new WebsocketNotificationDto();
            $notification->setUserIdToFindConnection((string)$order->getUser()->getId());
            $notification->setSocketEndpointName(AuthenticatedUserWebsocketEndpoint::SERVER_ENDPOINT_NAME);
            $notification->setFindConnectionBy(WebsocketNotificationDto::FIND_CONNECTION_BY_USER_ID);
            $notification->setFrontendHandlerName(AuthenticatedUserWebsocketEndpoint::FRONTEND_HANDLER_NAME);
            $notification->setActionName(AuthenticatedUserWebsocketEndpoint::FRONTEND_ACTION_HANDLE_POINTS_UPDATE);
            $notification->setMessage($this->translator->trans('payment.message.pointsHaveBeenUpdated'));

            $this->websocketNotificationService->sendAsyncNotification($notification);
        }

        return (new BaseApiResponse())->toJsonResponse();
    }

}