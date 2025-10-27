<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Propultech\WebpayPlusMallRest\Api\WebpayMallOrderDataRepositoryInterface;
use Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode;
use Propultech\WebpayPlusMallRest\Model\WebpayPlusMall;

class PaymentInfo extends Template
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param WebpayMallOrderDataRepositoryInterface $repository
     * @param array $data
     */
    public function __construct(
        Context                                                 $context,
        private Registry                                        $registry,
        private readonly WebpayMallOrderDataRepositoryInterface $webpayMallOrderDataRepository,
        private CommerceCode                                    $commerceCode,
        array                                                   $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * Get current order from registry
     */
    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof Order ? $order : null;
    }

    /**
     * Whether the order is paid with Webpay Plus Mall
     */
    public function isWebpayPlusMallOrder(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        $payment = $order->getPayment();
        return (bool)($payment && $payment->getMethod() === WebpayPlusMall::CODE);
    }

    /**
     * Get payment additional information stored during commit
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
        if (is_string($info)) {
            $decoded = json_decode($info, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    /**
     * Convenience getter for child details
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

    /**
     * @return array
     */
    public function getTransactions()
    {
        $order = $this->getOrder();
        $details = [];
        try {
            $transactions = $this->webpayMallOrderDataRepository->getByOrderId($order->getIncrementId());
            foreach ($transactions as $transaction) {
                $details[] = [
                    'commerce_name' => $this->commerceCode->getOptionText($transaction->getCommerceCode()),
                    'authorization_code' => $transaction->getAuthorizationCode(),
                ];
            }
        } catch (\Throwable) {
        }
        return $details;
    }


    /**
     * Converts the object to its HTML representation.
     *
     * @return string The HTML string if the order is a Webpay Plus Mall order, otherwise an empty string.
     */
    public function toHtml()
    {
        if (!$this->isWebpayPlusMallOrder()) {
            return '';
        }
        return parent::toHtml();
    }
}
