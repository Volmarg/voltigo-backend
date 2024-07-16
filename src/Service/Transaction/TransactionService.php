<?php

namespace App\Service\Transaction;

use App\DTO\Api\FinancesHub\TransactionDetailDTO;
use App\Entity\Ecommerce\Order;
use App\Entity\Email\Email;
use App\Enum\Email\TemplateIdentifierEnum;
use App\Exception\NotFoundException;
use App\Service\Api\FinancesHub\FinancesHubService;
use App\Service\Email\EmailAttachmentService;
use App\Service\File\TemporaryFileHandlerService;
use App\Service\Points\UserPointHistoryService;
use App\Service\Product\PointProductService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\Templates\Email\TransactionTemplatesService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FinancesHubBridge\Enum\PaymentStatusEnum;
use FinancesHubBridge\Exception\FinancesHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Stores logic related to receiving transactions for orders and in-general logic for activating order or
 * communicating the transaction details to user
 */
class TransactionService
{

    public function __construct(
        private readonly PointProductService         $pointProductService,
        private readonly EntityManagerInterface      $entityManager,
        private readonly TransactionTemplatesService $transactionTemplatesService,
        private readonly TranslatorInterface         $translator,
        private readonly ObjectSerializerService     $objectSerializerService,
        private readonly LoggerInterface             $logger,
        private readonly FinancesHubService          $financesHubService,
        private readonly EmailAttachmentService      $emailAttachmentService,
        private readonly TemporaryFileHandlerService $temporaryFileHandlerService,
        private readonly UserPointHistoryService     $userPointHistoryService
    ) {

    }

    /**
     * Will for example:
     * - grant points to user,
     * - change order status,
     * - send E-Mail regarding the order / transaction
     *
     * @param TransactionDetailDTO $transactionDetails
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function handleTransaction(TransactionDetailDTO $transactionDetails): void
    {
        $this->entityManager->beginTransaction();
        try {
            $order = $this->entityManager->find(Order::class, $transactionDetails->getOrderId());
            if (empty($order)) {
                throw new LogicException("Could not find order for id: {$transactionDetails->getOrderId()}", Response::HTTP_BAD_REQUEST);
            }

            if ($order->isActivated()) {
                throw new LogicException("This order is already activated! Order id: {$transactionDetails->getOrderId()}.", Response::HTTP_BAD_REQUEST);
            }

            if (!$transactionDetails->isTransactionSuccessful()) {
                $this->handleEmailing($transactionDetails, $order);
                $order->setStatus($transactionDetails->getStatus());
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                $this->entityManager->commit();
                return;
            }

            $grantedPoints = $this->pointProductService->calculateGrantedPoints($transactionDetails);
            if ($grantedPoints <= 0) {
                throw new LogicException("
                    The granted points are to low, something is wrong! Points granted: {$grantedPoints}.
                    OrderId: {$transactionDetails->getOrderId()}
                ", Response::HTTP_BAD_REQUEST);
            }

            if ($transactionDetails->isTransactionSuccessful()) {
                $this->markOrderAsActivated($order);
            }

            $this->handleEmailing($transactionDetails, $order, $grantedPoints);
            if (empty($order->getUser())) {
                throw new LogicException("There is no user related to this order on this step. Order id {$order->getId()}");
            }

            $userBeforeUpdatingPoints = clone $order->getUser();
            $order->getUser()->addPoints($grantedPoints);
            $order->setStatus($transactionDetails->getStatus());
            $this->userPointHistoryService->createAndSaveFromOrder($order, $userBeforeUpdatingPoints);

            $this->entityManager->commit();
        } catch (Throwable $t) {
            $this->entityManager->rollback();
            throw $t;
        }
    }

    /**
     * @param Order $order
     */
    private function markOrderAsActivated(Order $order): void
    {
        $order->setActivated(true);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    /**
     * @param TransactionDetailDTO $transactionDetails
     * @param Order                $order
     * @param int|null             $grantedPoints
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     * @throws Throwable
     */
    private function handleEmailing(
        TransactionDetailDTO $transactionDetails,
        Order                $order,
        ?int                 $grantedPoints = null
    ): void
    {
        $emailStatus = Email::KEY_STATUS_PENDING;
        $failedEmailLogData = [
            "info"               => "Not sending this E-Mail to user, it has to be sent manually later",
            "info-2"             => "E-Mail is saved in DB with error status so it won't be sent out",
            'transactionDetails' => $this->objectSerializerService->toJson($transactionDetails),
        ];

        if ($transactionDetails->isTransactionSuccessful()) {
            $body       = $this->transactionTemplatesService->renderTransactionSuccessful($transactionDetails, $order, $grantedPoints);
            $subject    = $this->translator->trans('mails.transaction.success.subject');
            $templateId = TemplateIdentifierEnum::TRANSACTION_SUCCESSFUL->name;
        } else {
            $body       = $this->transactionTemplatesService->renderTransactionFailed($transactionDetails, $order);
            $subject    = $this->translator->trans('mails.transaction.fail.subject');
            $templateId = TemplateIdentifierEnum::TRANSACTION_FAILED->name;

            if (empty($transactionDetails->getRequestData()) && empty($transactionDetails->getPaymentIdentifier())) {
                $emailStatus = Email::KEY_STATUS_ERROR;
                $this->logger->critical("Got some fishy case where transaction has no request data nor has it payment id", $failedEmailLogData);
            }

            if (
                    $transactionDetails->getStatus() === PaymentStatusEnum::TRIGGERED->name
                ||  $transactionDetails->getStatus() === PaymentStatusEnum::PENDING->name
            ) {
                $emailStatus = Email::KEY_STATUS_ERROR;
                $this->logger->critical("Transaction status is '{$transactionDetails->getStatus()}' - such situation should not have place", $failedEmailLogData);
            }
        }

        $this->buildEmailWithAttachments($transactionDetails, $order, $emailStatus, $subject, $body, $templateId);

        $order->setMailed(true);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    /**
     * Will build and save the E-Mail used in {@see TransactionService::handleTransaction()}
     *
     * @param TransactionDetailDTO $transactionDetails
     * @param Order                $order
     * @param string               $emailStatus
     * @param string               $subject
     * @param string               $body
     * @param string               $templateId
     *
     * @throws FinancesHubBridgeException
     * @throws GuzzleException
     * @throws Exception
     */
    private function buildEmailWithAttachments(
        TransactionDetailDTO $transactionDetails,
        Order                $order,
        string               $emailStatus,
        string               $subject,
        string               $body,
        string               $templateId
    ): void
    {
        $email = new Email();
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setStatus($emailStatus);
        $email->setRecipients([$order->getUser()->getEmail()]);
        $email->setIdentifier($templateId);

        // must be persisted on this place as the id is going to be used later on
        $this->entityManager->persist($email);
        $this->entityManager->flush();

        if ($transactionDetails->isTransactionSuccessful()) {
            $invoiceFileContent = $this->financesHubService->getInvoicePdfContent($transactionDetails->getOrderId());
            $temporaryFileDto   = $this->temporaryFileHandlerService->saveFile($invoiceFileContent, "pdf");
            $attachment         = $this->emailAttachmentService->prepareAttachment($temporaryFileDto->getFileName(), $temporaryFileDto->getAbsoluteFilePath(), $email);

            $temporaryFileDto->removeFile();

            $email->addAttachment($attachment);
            $attachment->setEmail($email);
            $this->entityManager->persist($attachment);
        }

        $this->entityManager->flush();
    }
}