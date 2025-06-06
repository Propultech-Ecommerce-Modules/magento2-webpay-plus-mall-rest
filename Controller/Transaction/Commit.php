<?php

namespace Propultech\WebpayPlusMallRest\Controller\Transaction;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as DbTransaction;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRest;
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
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var OrderSender
     */
    private OrderSender $orderSender;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * @var InvoiceService
     */
    private InvoiceService $invoiceService;

    /**
     * @var DbTransaction
     */
    private DbTransaction $dbTransaction;

    /**
     * @var PluginLogger
     */
    private PluginLogger $log;

    /**
     * @var TransbankSdkWebpayPlusMallRestFactory
     */
    private TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param PageFactory $resultPageFactory
     * @param ConfigProvider $configProvider
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param PluginLogger $logger
     * @param TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        PageFactory $resultPageFactory,
        ConfigProvider $configProvider,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        DbTransaction $dbTransaction,
        PluginLogger $logger,
        TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->configProvider = $configProvider;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->log = $logger;
        $this->transbankSdkFactory = $transbankSdkFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $tokenWs = $this->getRequest()->getParam('token_ws');
        $this->log->logInfo('Commit transaction with token: ' . ($tokenWs ?? 'null'));

        if (empty($tokenWs)) {
            $this->messageManager->addErrorMessage(__('Invalid transaction token'));
            return $this->_redirect('checkout/cart');
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
                    $this->processSuccessfulPayment($order, $commitResponse);
                    return $this->_redirect('checkout/onepage/success');
                } else {
                    $this->processFailedPayment($order, $commitResponse);
                    $this->messageManager->addErrorMessage(__('Payment was not approved by the bank'));
                    return $this->_redirect('checkout/cart');
                }
            } else if (isset($commitResponse['error'])) {
                $this->messageManager->addErrorMessage(__('Error: %1', $commitResponse['detail'] ?? $commitResponse['error']));
                return $this->_redirect('checkout/cart');
            } else {
                $this->messageManager->addErrorMessage(__('Invalid transaction response'));
                return $this->_redirect('checkout/cart');
            }
        } catch (LocalizedException $e) {
            $this->log->logError('LocalizedException: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('checkout/cart');
        } catch (NoSuchEntityException $e) {
            $this->log->logError('NoSuchEntityException: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Order not found'));
            return $this->_redirect('checkout/cart');
        } catch (\Exception $e) {
            $this->log->logError('Error in commit transaction: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Error processing payment: %1', $e->getMessage()));
            return $this->_redirect('checkout/cart');
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
     * @return void
     */
    private function processSuccessfulPayment(Order $order, MallTransactionCommitResponse $commitResponse): void
    {
        $orderStatusSuccess = $this->configProvider->getOrderSuccessStatus();
        $message = "<h3>Pago exitoso con Webpay Plus Mall</h3><br>" . json_encode($commitResponse);

        // Set payment information
        $payment = $order->getPayment();
        $payment->setLastTransId($commitResponse->details[0]->authorizationCode);
        $payment->setTransactionId($commitResponse->details[0]->authorizationCode);
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $commitResponse]);
        $payment->setMethod(WebpayPlusMall::CODE);

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
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
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
            $searchCriteria = $this->_objectManager->create(
                \Magento\Framework\Api\SearchCriteriaBuilder::class
            )->addFilter('increment_id', $incrementId, 'eq')
                ->create();

            $orderList = $this->orderRepository->getList($searchCriteria);
            if ($orderList->getTotalCount()) {
                return $orderList->getItems()[0];
            }

            return null;
        } catch (\Exception $e) {
            $this->log->logError('Error loading order by increment ID: ' . $e->getMessage());
            return null;
        }
    }
}
