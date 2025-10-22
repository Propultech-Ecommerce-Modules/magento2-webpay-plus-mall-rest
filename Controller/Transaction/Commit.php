<?php

namespace Propultech\WebpayPlusMallRest\Controller\Transaction;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DB\Transaction as DbTransaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRestFactory;
use Propultech\WebpayPlusMallRest\Model\WebpayPlusMall;
use Psr\Log\LoggerInterface;
use Transbank\Webpay\WebpayPlus\Responses\MallTransactionCommitResponse;

/**
 * Controller for committing Webpay Plus Mall transactions.
 */
class Commit extends Action
{
    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ConfigProvider $configProvider
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context                                                $context,
        private readonly CheckoutSession                       $checkoutSession,
        private readonly ConfigProvider                        $configProvider,
        private readonly OrderSender                           $orderSender,
        private readonly InvoiceSender                         $invoiceSender,
        private readonly InvoiceService                        $invoiceService,
        private readonly DbTransaction                         $dbTransaction,
        private readonly ResourceConnection                    $resourceConnection,
        private readonly LoggerInterface                       $logger,
        private readonly TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory,
        private readonly OrderRepositoryInterface              $orderRepository,
        private readonly OrderFactory                          $orderFactory
    )
    {
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $tokenWs = $this->getRequest()->getParam('token_ws');
        $this->logger->logInfo('Commit transaction with token: ' . ($tokenWs ?? 'null'));

        if (empty($tokenWs)) {
            return $this->redirectToCartWithError(__('Invalid transaction token'));
        }

        try {
            $transbankSdkWebpay = $this->transbankSdkFactory->create([
                'logger' => $this->logger,
                'config' => $this->configProvider->getPluginConfig()
            ]);

            $commitResponse = $transbankSdkWebpay->commitTransaction($tokenWs);
            $this->logger->logInfo('Commit response: ' . json_encode($commitResponse));

            if (isset($commitResponse->buyOrder)) {
                $order = $this->getOrderByIncrementId($commitResponse->buyOrder);
                if (!$order || !$order->getId()) {
                    throw new LocalizedException(__('Order not found for buyOrder: %1', $commitResponse->buyOrder));
                }

                // Check if all details are approved
                $allApproved = $this->areAllDetailsApproved($commitResponse);

                if ($allApproved) {
                    $this->processSuccessfulPayment($order, $commitResponse, $tokenWs);
                    return $this->_redirect('checkout/onepage/success');
                } else {
                    $this->processFailedPayment($order, $commitResponse);
                    return $this->redirectToCartWithError(__('Payment was not approved by the bank'));
                }
            } else if (isset($commitResponse['error'])) {
                return $this->redirectToCartWithError(__('Error: %1', $commitResponse['detail'] ?? $commitResponse['error']));
            } else {
                return $this->redirectToCartWithError(__('Invalid transaction response'));
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->logError('NoSuchEntityException: ' . $e->getMessage());
            return $this->redirectToCartWithError(__('Order not found'));
        } catch (LocalizedException $e) {
            $this->logger->logError('LocalizedException: ' . $e->getMessage());
            return $this->redirectToCartWithError(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->logError('Error in commit transaction: ' . $e->getMessage());
            return $this->redirectToCartWithError(__('Error processing payment: %1', $e->getMessage()));
        }
    }

    /**
     * Check if all transaction details are approved
     *
     * @param MallTransactionCommitResponse $commitResponse
     * @return bool
     */
    private function areAllDetailsApproved(MallTransactionCommitResponse $commitResponse): bool
    {
        if (!isset($commitResponse->details) || !is_array($commitResponse->details)) {
            return false;
        }

        foreach ($commitResponse->details as $detail) {
            if (!isset($detail->responseCode) || $detail->responseCode !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process successful payment
     *
     * @param Order $order
     * @param MallTransactionCommitResponse $commitResponse
     * @param string $tokenWs
     * @return void
     */
    private function processSuccessfulPayment(Order $order, MallTransactionCommitResponse $commitResponse, string $tokenWs): void
    {
        $orderStatusSuccess = $this->configProvider->getOrderSuccessStatus();
        $message = "<h3>Pago exitoso con Webpay Plus Mall</h3><br>" . json_encode($commitResponse);

        // Set payment information
        $payment = $order->getPayment();
        $firstDetail = $commitResponse->details[0] ?? null;
        if ($firstDetail && isset($firstDetail->authorizationCode)) {
            $payment->setLastTransId($firstDetail->authorizationCode);
            $payment->setTransactionId($firstDetail->authorizationCode);
        }
        $payment->setMethod(WebpayPlusMall::CODE);

        // Build additional information (non-sensitive)
        $cardNumber = $commitResponse->cardNumber ?? null;
        $last4 = $cardNumber ? substr(preg_replace('/\D/', '', (string)$cardNumber), -4) : null;

        $details = [];
        if (!empty($commitResponse->details) && is_array($commitResponse->details)) {
            foreach ($commitResponse->details as $d) {
                $details[] = [
                    'commerce_code' => $d->commerceCode ?? null,
                    'child_buy_order' => $d->buyOrder ?? null,
                    'amount' => $d->amount ?? null,
                    'authorization_code' => $d->authorizationCode ?? null,
                    'payment_type_code' => $d->paymentTypeCode ?? null,
                    'response_code' => $d->responseCode ?? null,
                    'installments_number' => $d->installmentsNumber ?? null,
                    'status' => $d->status ?? null,
                ];
            }
        }

        $additional = [
            'token_ws' => $tokenWs,
            'buy_order' => $commitResponse->buyOrder ?? $order->getIncrementId(),
            'session_id' => $commitResponse->sessionId ?? null,
            'transaction_date' => $commitResponse->transactionDate ?? null,
            'accounting_date' => $commitResponse->accountingDate ?? null,
            'vci' => $commitResponse->vci ?? null,
            'card' => [
                'last4' => $last4,
            ],
            'details' => $details,
        ];

        // Persist additional information keys individually to avoid overwriting
        $payment->setAdditionalInformation('webpayplusmall', $additional);
        $payment->setAdditionalInformation(Transaction::RAW_DETAILS, json_encode($commitResponse));

        // Set order status
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus($orderStatusSuccess);
        $order->addStatusToHistory($order->getStatus(), $message);

        // Create invoice if configured
        if ($this->configProvider->getInvoiceSettings() === 'transbank' && $order->canInvoice()) {
            $this->createInvoice($order);
        }

        // Persist transaction rows
        $this->persistTransactionData($order, $commitResponse, $tokenWs, $additional);

        // Save order before sending email
        $this->orderRepository->save($order);

        // Send email if configured (after save)
        if ($this->configProvider->getEmailBehavior() === 'after_payment') {
            try {
                $order = $this->orderRepository->get($order->getId());
            } catch (\Exception $e) {
                $this->logger->logError('Error reloading order before email: ' . $e->getMessage());
            }
            $this->orderSender->send($order);
        }

        // Set checkout session data
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
    }

    /**
     * Persist transaction rows into webpay_mall_order_data table
     *
     * @param Order $order
     * @param MallTransactionCommitResponse $commitResponse
     * @param string $tokenWs
     * @param array $metadata
     */
    private function persistTransactionData(Order $order, MallTransactionCommitResponse $commitResponse, string $tokenWs, array $metadata): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('webpay_mall_order_data');

            $buyOrder = (string)($commitResponse->buyOrder ?? $order->getIncrementId());
            $sessionId = (string)($commitResponse->sessionId ?? '');
            $quoteId = (string)$order->getQuoteId();
            $product = WebpayPlusMall::PRODUCT_NAME;
            $environment = (string)($this->configProvider->getPluginConfig()['ENVIRONMENT'] ?? '');

            $commerceCodes = [];
            if (!empty($commitResponse->details) && is_array($commitResponse->details)) {
                foreach ($commitResponse->details as $d) {
                    if (isset($d->commerceCode)) {
                        $commerceCodes[] = (string)$d->commerceCode;
                    }
                }
            }
            $commerceCodesJson = json_encode($commerceCodes);

            // Prevent duplicates for the same token
            $connection->delete($table, ['token = ?' => substr($tokenWs, 0, 100)]);

            $rows = [];
            if (!empty($commitResponse->details) && is_array($commitResponse->details)) {
                foreach ($commitResponse->details as $d) {
                    $rows[] = [
                        'order_id' => substr((string)$order->getIncrementId(), 0, 60),
                        'buy_order' => substr($buyOrder, 0, 20),
                        'child_buy_order' => substr((string)($d->buyOrder ?? ''), 0, 20),
                        'commerce_codes' => $commerceCodesJson ?: '[]',
                        'child_commerce_code' => substr((string)($d->commerceCode ?? ''), 0, 60),
                        'amount' => (int)($d->amount ?? 0),
                        'token' => substr($tokenWs, 0, 100),
                        'transbank_status' => json_encode([
                            'status' => $d->status ?? null,
                            'response_code' => $d->responseCode ?? null,
                            'authorization_code' => $d->authorizationCode ?? null,
                        ]),
                        'session_id' => substr($sessionId, 0, 20),
                        'quote_id' => substr($quoteId, 0, 20),
                        'payment_status' => 'APPROVED',
                        'metadata' => json_encode($metadata),
                        'product' => substr($product, 0, 50),
                        'environment' => substr($environment, 0, 50),
                    ];
                }
            } else {
                // Fallback single row if no details present
                $rows[] = [
                    'order_id' => substr((string)$order->getIncrementId(), 0, 60),
                    'buy_order' => substr($buyOrder, 0, 20),
                    'child_buy_order' => substr($buyOrder, 0, 20),
                    'commerce_codes' => $commerceCodesJson ?: '[]',
                    'child_commerce_code' => substr((string)($this->configProvider->getPluginConfig()['COMMERCE_CODE'] ?? ''), 0, 60),
                    'amount' => (int)round((float)$order->getGrandTotal()),
                    'token' => substr($tokenWs, 0, 100),
                    'transbank_status' => json_encode(['status' => 'APPROVED']),
                    'session_id' => substr($sessionId, 0, 20),
                    'quote_id' => substr($quoteId, 0, 20),
                    'payment_status' => 'APPROVED',
                    'metadata' => json_encode($metadata),
                    'product' => substr($product, 0, 50),
                    'environment' => substr($environment, 0, 50),
                ];
            }

            if (!empty($rows)) {
                $connection->insertMultiple($table, $rows);
            }
        } catch (\Throwable $e) {
            $this->logger->logError('Error persisting webpay_mall_order_data: ' . $e->getMessage());
        }
    }

    /**
     * Process failed payment
     *
     * @param Order $order
     * @param object $commitResponse
     * @return void
     */
    private function processFailedPayment(Order $order, $commitResponse): void
    {
        $orderStatusCanceled = $this->configProvider->getOrderErrorStatus();
        $message = "<h3>Pago rechazado con Webpay Plus Mall</h3><br>" . json_encode($commitResponse);

        $order->cancel();
        $order->setStatus($orderStatusCanceled);
        $order->addStatusToHistory($order->getStatus(), $message);
        $this->orderRepository->save($order);
    }

    /**
     * Create invoice for order
     *
     * @param Order $order
     * @return void
     */
    private function createInvoice(Order $order): void
    {
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $this->dbTransaction->addObject($invoice)->addObject($invoice->getOrder())->save();
            $invoice->pay();
            $invoice->save();
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->logger->logError('Error creating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Get order by increment ID
     *
     * @param string $incrementId
     * @return Order|null
     */
    private function getOrderByIncrementId(string $incrementId): ?Order
    {
        try {
            return $this->orderFactory->create()->loadByIncrementId($incrementId);
        } catch (\Exception $e) {
            $this->logger->logError('Error loading order by increment ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Restore the customer's quote to the session
     */
    private function restoreQuote(): void
    {
        try {
            $this->checkoutSession->restoreQuote();
        } catch (\Exception $e) {
            $this->logger->logError('Error restoring quote: ' . $e->getMessage());
        }
    }

    /**
     * Add error message, restore quote and redirect to cart
     */
    private function redirectToCartWithError($message): \Magento\Framework\App\ResponseInterface
    {
        if ($message) {
            $this->messageManager->addErrorMessage($message);
        }
        $this->restoreQuote();
        return $this->_redirect('checkout/cart');
    }
}
