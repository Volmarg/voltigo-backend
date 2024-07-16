<?php

namespace App\Command\Finances;

use App\Action\Payment\PreparedPaymentAction;
use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Entity\Ecommerce\Order;
use App\Enum\Payment\PaymentProcessStateEnum;
use App\Repository\Ecommerce\OrderRepository;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Payment\PaymentTransferService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

class FinanceHubTransferOrdersCommand extends AbstractCommand
{
    const COMMAND_NAME = "finances:transfer-orders";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Takes any orders that are suitable for transferring to finances hub (were stuck, failed etc.)");
    }

    /**
     * @param FinancesHubService     $financesHubService
     * @param OrderRepository        $orderRepository
     * @param EntityManagerInterface $entityManager
     * @param PaymentTransferService $paymentTransferService
     * @param ConfigLoader           $configLoader
     * @param KernelInterface        $kernel
     */
    public function __construct(
        private readonly FinancesHubService     $financesHubService,
        private readonly OrderRepository        $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaymentTransferService $paymentTransferService,
        ConfigLoader                            $configLoader,
        KernelInterface                         $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
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
            $orders = $this->orderRepository->getStuckOrders(PreparedPaymentAction::MAX_HOURS_OFFSET_TO_FINISH);
            foreach ($orders as $order) {
                $this->handleOrder($order);
            }

        } catch (Exception|TypeError $e) {
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Will handle transferring single order.
     *
     * @param Order $order
     *
     * @throws GuzzleException
     */
    private function handleOrder(Order $order): void
    {
        $this->io->info("Now handling order: {$order->getId()}");
        $paymentState = PaymentProcessStateEnum::BEGINNING;

        try {
            if ($order->getProductSnapshots()->count() > 1) {
                $msg = "This order ({$order->getId()}) has more than one related products. This is not allowed!";
                $this->logger->critical($msg);
                $this->io->info($msg);
                return;
            }

            $productSnapshot     = $order->getProductSnapshots()->first();
            $productFromSnapshot = $productSnapshot->getProduct();
            if (is_null($productFromSnapshot)) {
                $msg = "Original product for ProductSnapshot of id: {$productSnapshot->getId()}, does not exist for order {$order->getId()}";
                $this->logger->critical($msg);
                $this->io->info($msg);
                return;
            }

            $paymentBag  = $this->paymentTransferService->buildPaymentBag($order, $productSnapshot, $productFromSnapshot);
            $transaction = $this->paymentTransferService->buildTransaction($productFromSnapshot, $productSnapshot, $paymentBag, $order);

            $paymentState = PaymentProcessStateEnum::REAL_PAYMENT_BEGAN_DATA_SENT_TO_FINANCES_HUB;
            $this->financesHubService->insertTransaction($transaction);

            $order->setTransferredToFinancesHub(true);
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        } catch (Exception|TypeError $ein) {
            $this->io->info("Failed handling order: {$order->getId()}. Got exception: {$ein->getMessage()}");
            $this->logger->critical("Could not transfer order", [
                "paymentState" => $paymentState->name,
                "exception" => [
                    "msg"   => $ein->getMessage(),
                    "trace" => $ein->getTrace(),
                ],
            ]);
        }
    }

}
