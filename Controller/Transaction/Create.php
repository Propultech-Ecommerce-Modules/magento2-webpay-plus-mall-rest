<?php

namespace Propultech\WebpayPlusMallRest\Controller\Transaction;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRest;
use Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRestFactory;
use Propultech\WebpayPlusMallRest\Model\TransactionDetailsBuilder;
use Propultech\WebpayPlusMallRest\Model\WebpayPlusMall;
use Transbank\Webpay\Helper\PluginLogger;

/**
 * Controller for creating Webpay Plus Mall transactions.
 */
class Create extends Action
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var TransactionDetailsBuilder
     */
    private TransactionDetailsBuilder $transactionDetailsBuilder;

    /**
     * @var PluginLogger
     */
    private PluginLogger $log;

    /**
     * @var TransbankSdkWebpayPlusMallRestFactory
     */
    private TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param QuoteManagement $quoteManagement
     * @param StoreManagerInterface $storeManager
     * @param ConfigProvider $configProvider
     * @param TransactionDetailsBuilder $transactionDetailsBuilder
     * @param PluginLogger $logger
     * @param TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        TransactionDetailsBuilder $transactionDetailsBuilder,
        PluginLogger $logger,
        TransbankSdkWebpayPlusMallRestFactory $transbankSdkFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteManagement = $quoteManagement;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->transactionDetailsBuilder = $transactionDetailsBuilder;
        $this->log = $logger;
        $this->transbankSdkFactory = $transbankSdkFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $response = null;
        $order = null;
        $orderStatusCanceled = $this->configProvider->getOrderErrorStatus();
        $orderStatusPendingPayment = $this->configProvider->getOrderPendingStatus();

        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if (!$order || !$order->getId()) {
                throw new LocalizedException(__('Order not found'));
            }

            $orderId = $order->getIncrementId();
            $grandTotal = (int)round($order->getGrandTotal());

            $this->log->logInfo('Creating transaction for order: ' . $orderId . ', amount: ' . $grandTotal);

            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $returnUrl = $baseUrl . $this->configProvider->getPluginConfig()['URL_RETURN'];
            $buyOrder = $orderId;
            $sessionId = $order->getCustomerId() ? (string)$order->getCustomerId() : 'guest_' . time();

            // Build transaction details
            $details = $this->transactionDetailsBuilder->build($order);
            if (empty($details)) {
                throw new LocalizedException(__('Could not build transaction details'));
            }

            $this->log->logInfo('Transaction details: ' . json_encode($details));

            // Create transaction
            $transbankSdkWebpay = $this->transbankSdkFactory->create([
                'logger' => $this->log,
                'config' => $this->configProvider->getPluginConfig()
            ]);

            $response = $transbankSdkWebpay->createTransaction($buyOrder, $sessionId, $returnUrl, $details);
            $dataLog = ['buyOrder' => $buyOrder, 'sessionId' => $sessionId];
            $message = "<h3>Esperando pago con Webpay Plus Mall</h3><br>" . json_encode($dataLog);

            if (isset($response['token_ws']) && isset($response['url'])) {
                $this->updateOrderStatus($order, $orderStatusPendingPayment, $message);
            } else {
                $this->cancelOrder($order, $orderStatusCanceled,
                    '<h3>Error en creación de transacción con Webpay Plus Mall</h3><br>' . json_encode($response)
                );
            }
        } catch (LocalizedException $e) {
            $this->log->logError('LocalizedException: ' . $e->getMessage());
            $response = ['error' => $e->getMessage()];
            $this->handleOrderError($order, $orderStatusCanceled, $e->getMessage());
        } catch (NoSuchEntityException $e) {
            $this->log->logError('NoSuchEntityException: ' . $e->getMessage());
            $response = ['error' => __('Store or entity not found')];
            $this->handleOrderError($order, $orderStatusCanceled, $e->getMessage());
        } catch (\Exception $e) {
            $this->log->logError('Exception: ' . $e->getMessage());
            $response = ['error' => __('Error creating transaction: %1', $e->getMessage())];
            $this->handleOrderError($order, $orderStatusCanceled, $e->getMessage());
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($response ?? ['error' => __('Unknown error occurred')]);

        return $result;
    }

    /**
     * Update order status and add status history comment
     *
     * @param Order $order
     * @param string $status
     * @param string $message
     * @return void
     */
    private function updateOrderStatus(Order $order, string $status, string $message): void
    {
        $order->setStatus($status);
        $order->addStatusToHistory($status, $message);
        $order->save();
    }

    /**
     * Cancel order and update status
     *
     * @param Order $order
     * @param string $status
     * @param string $message
     * @return void
     */
    private function cancelOrder(Order $order, string $status, string $message): void
    {
        $order->cancel();
        $order->setStatus($status);
        $order->addStatusToHistory($status, $message);
        $order->save();
    }

    /**
     * Handle order error
     *
     * @param Order|null $order
     * @param string $errorStatus
     * @param string $errorMessage
     * @return void
     */
    private function handleOrderError(?Order $order, string $errorStatus, string $errorMessage): void
    {
        if ($order && $order->getId()) {
            $message = 'Error al crear transacción: ' . $errorMessage;
            $this->cancelOrder($order, $errorStatus, $message);
        }
    }
}
