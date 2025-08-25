<?php

namespace Propultech\WebpayPlusMallRest\Controller\Transaction;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
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
use Magento\Sales\Model\Service\InvoiceService;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRestFactory;
use Propultech\WebpayPlusMallRest\Model\WebpayPlusMall;
use Transbank\Webpay\Helper\PluginLogger;
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
     * @param PluginLogger $log
     * @param TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context                                                $context,
        private readonly CheckoutSession                       $checkoutSession,
        private readonly ConfigProvider                        $configProvider,
        private readonly OrderSender                           $orderSender,
        private readonly InvoiceSender                         $invoiceSender,
        private readonly InvoiceService                        $invoiceService,
        private readonly DbTransaction                         $dbTransaction,
        private readonly PluginLogger                          $log,
        private readonly TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory,
        private readonly OrderRepositoryInterface              $orderRepository
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
        $this->log->logInfo('Commit transaction with token: ' . ($tokenWs ?? 'null'));

        if (empty($tokenWs)) {
            return $this->redirectToCartWithError(__('Invalid transaction token'));
        }

        try {
            $transbankSdkWebpay = $this->transbankSdkFactory->create([
                'logger' => $this->log,
                'config' => $this->configProvider->getPluginConfig()
            ]);

            $commitResponse = $transbankSdkWebpay->commitTransaction($tokenWs);
            $this->log->logInfo('Commit response: ' . json_encode($commitResponse));

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
            $this->log->logError('NoSuchEntityException: ' . $e->getMessage());
            return $this->redirectToCartWithError(__('Order not found'));
        } catch (LocalizedException $e) {
            $this->log->logError('LocalizedException: ' . $e->getMessage());
            return $this->redirectToCartWithError(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->log->logError('Error in commit transaction: ' . $e->getMessage());
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
        if ($this->configProvider->getInvoiceSettings() === 'automatic' && $order->canInvoice()) {
            $this->createInvoice($order);
        }

        // Send email if configured
        if ($this->configProvider->getEmailBehavior() === 'after_payment') {
            $this->orderSender->send($order);
        }

        $this->orderRepository->save($order);

        // Set checkout session data
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
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
            $invoice->pay();
            $this->dbTransaction->addObject($invoice)->addObject($invoice->getOrder())->save();
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->log->logError('Error creating invoice: ' . $e->getMessage());
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
            $objectManager = ObjectManager::getInstance();
            return $objectManager->create(Order::class)->loadByIncrementId($incrementId);
        } catch (\Exception $e) {
            $this->log->logError('Error loading order by increment ID: ' . $e->getMessage());
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
            $this->log->logError('Error restoring quote: ' . $e->getMessage());
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
