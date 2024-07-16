<?php

namespace App\Service\Templates\Email;

use App\Controller\Core\ConfigLoader;
use App\DTO\Api\FinancesHub\TransactionDetailDTO;
use App\Entity\Ecommerce\Order;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Handles twig templates logic for sending E-Mails regarding the transactions for orders
 */
class TransactionTemplatesService
{
    /**
     * @var Environment $environment
     */
    private Environment $environment;

    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    /**
     * @param Environment  $environment
     * @param ConfigLoader $configLoader
     */
    public function __construct(
        Environment  $environment,
        ConfigLoader $configLoader,
    )
    {
        $this->configLoader   = $configLoader;
        $this->environment    = $environment;
    }

    /**
     * E-Mail template for: transaction finished with success
     *
     * @param TransactionDetailDTO $transactionDetails
     * @param Order                $order
     * @param int                  $grantedPoints
     *
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTransactionSuccessful(TransactionDetailDTO $transactionDetails, Order $order, int $grantedPoints): string
    {
        $templateData = [
            'user'            => $order->getUser(),
            'projectName'     => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'isEqualDemanded' => $transactionDetails->isTransactionSuccessfulAndEqualDemanded(),
            'expectedPayment' => $order->getCost()->getTotalWithTax(),
            'receivedPayment' => $transactionDetails->getMoneyAcknowledged(),
            'expectedPoints'  => $order->getExpectedPoints(),
            'grantedPoints'   => $grantedPoints,
        ];

        return $this->environment->render('mail/order-and-transaction/success.twig', $templateData);
    }

    /**
     * E-Mail template for: transaction failed
     *
     * @param TransactionDetailDTO $transactionDetails
     * @param Order                $order
     *
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTransactionFailed(TransactionDetailDTO $transactionDetails, Order $order): string
    {
        $templateData = [
            'user'                   => $order->getUser(),
            'projectName'            => $this->configLoader->getConfigLoaderProject()->getProjectName(),
            'transactionStatus'      => $transactionDetails->getStatus(),
            'paymentToolName'        => $transactionDetails->getPaymentToolName(),
            'paymentToolContactPage' => $transactionDetails->getPaymentToolContactPage(),
            'paymentIdentifier'      => $transactionDetails->getPaymentIdentifier(),
            'requestData'            => $transactionDetails->getRequestData(),

            # This array is provided on purpose because there is a debug route for easy E-Mail template rendering (using Postman for that)
            'status' => [
                "inProgress" => PaymentStatusEnum::IN_PROGRESS->name,
                "error"      => PaymentStatusEnum::ERROR->name,
                "unknown"    => PaymentStatusEnum::UNKNOWN->name,
                "cancelled"  => PaymentStatusEnum::CANCELLED->name,
                "returned"   => PaymentStatusEnum::RETURNED->name,
            ]
        ];

        return $this->environment->render('mail/order-and-transaction/failure.twig', $templateData);
    }

}