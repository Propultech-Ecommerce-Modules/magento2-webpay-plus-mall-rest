<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class TransactionDetailsBuilder
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Build transaction details array for Webpay Plus Mall
     *
     * @param Order $order
     * @param string $buyOrderPrefix
     * @return array
     */
    public function build(Order $order, string $buyOrderPrefix = '200000'): array
    {
        $items = $order->getAllItems();
        $commerceCodeGroups = [];
        $defaultCommerceCode = $this->getDefaultChildCommerceCode();

        // Group items by commerce code
        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById($item->getProductId());
                $commerceCode = $product->getData('webpay_mall_commerce_code');

                if (!isset($commerceCodeGroups[$commerceCode])) {
                    $commerceCodeGroups[$commerceCode] = 0;
                }

                $commerceCodeGroups[$commerceCode] += $item->getRowTotalInclTax();
            } catch (\Exception $e) {
                // Log error and continue with next item
                continue;
            }
        }

        // Build transaction details array
        $details = [];
        $i = 0;
        foreach ($commerceCodeGroups as $commerceCode => $amount) {
            if (empty($commerceCode)) {
                continue; // Skip empty commerce codes
            }

            $details[] = [
                "commerce_code" => $commerceCode,
                "buy_order" => $buyOrderPrefix . $order->getId() . '_' . $i,
                "amount" => (int)round($amount),
                "installments_number" => 1
            ];
            $i++;
        }

        // If no details were created, use the default commerce code with the total amount
        if (empty($details) && !empty($defaultCommerceCode)) {
            $details[] = [
                "commerce_code" => $defaultCommerceCode,
                "buy_order" => $buyOrderPrefix . $order->getId() . '_0',
                "amount" => (int)round($order->getGrandTotal()),
                "installments_number" => 1
            ];
        }

        return $details;
    }

    /**
     * Get default child commerce code from configuration
     *
     * @return string
     */
    private function getDefaultChildCommerceCode(): string
    {
        $commerceCodesJson = $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/commerce_codes',
            ScopeInterface::SCOPE_STORE
        );

        $commerceCodes = json_decode($commerceCodesJson, true);

        if (!empty($commerceCodes) && is_array($commerceCodes) && isset($commerceCodes[0]['commerce_code'])) {
            return (string)$commerceCodes[0]['commerce_code'];
        }

        return '';
    }
}
