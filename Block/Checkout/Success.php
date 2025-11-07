<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Block\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Propultech\WebpayPlusMallRest\Model\WebpayPlusMall;

class Success extends Template
{
    public function __construct(
        Context $context,
        private CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the last real order (if available)
     */
    public function getOrder(): ?Order
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if ($order && $order->getId()) {
                return $order;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    /**
     * Whether the payment method is Webpay Plus Mall for the last order
     */
    public function isWebpayPlusMallOrder(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        $payment = $order->getPayment();
        return $payment && $payment->getMethod() === WebpayPlusMall::CODE;
    }

    /**
     * Get additional information saved during commit
     * Returns null when not available
     */
    public function getPaymentInfo(): ?array
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }
        $info = $payment->getAdditionalInformation('webpayplusmall');
        if (is_array($info)) {
            return $info;
        }
        // If stored as JSON string by some customization, try to decode
        if (is_string($info)) {
            $decoded = json_decode($info, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    /**
     * Convenience getter for details array
     */
    public function getChildDetails(): array
    {
        $info = $this->getPaymentInfo();
        if (!$info) {
            return [];
        }
        $details = $info['details'] ?? [];
        return is_array($details) ? $details : [];
    }
}
