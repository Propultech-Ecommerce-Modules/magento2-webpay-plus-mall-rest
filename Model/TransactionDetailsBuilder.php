<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
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
        private ScopeConfigInterface       $scopeConfig
    )
    {
    }

    /**
     * Build transaction details array for Webpay Plus Mall
     *
     * @param Order $order
     * @param string $buyOrderPrefix
     * @return array
     */
    public function build(Order $order, string $buyOrderPrefix = ''): array
    {
        $items = $order->getAllItems();
        $commerceCodeGroups = [];
        $defaultCommerceCode = $this->getDefaultChildCommerceCode();

        // Group items by commerce code
        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById($item->getProductId());
                $commerceCode = $product->getData('webpay_mall_commerce_code') ?? $defaultCommerceCode;

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
                "buy_order" => $buyOrderPrefix . $order->getIncrementId() . '_' . $i,
                "amount" => (int)round($amount)
            ];
            $i++;
        }

        // If no details were created, use the default commerce code with the total amount
        if (empty($details) && !empty($defaultCommerceCode)) {
            $details[] = [
                "commerce_code" => $defaultCommerceCode,
                "buy_order" => $buyOrderPrefix . $order->getId() . '_0',
                "amount" => (int)round($order->getGrandTotal())
            ];
        }

        return $details;
    }

    /**
     * Get default child commerce code from configuration
     *
     * @return string
     * @throws LocalizedException
     */
    private function getDefaultChildCommerceCode(): string
    {
        $commerceCodesJson = $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/commerce_codes',
            ScopeInterface::SCOPE_STORE
        );

        $commerceCodes = json_decode($commerceCodesJson, true);
        $first = array_key_first($commerceCodes);

        if (!empty($commerceCodes) && is_array($commerceCodes) && isset($commerceCodes[$first]['commerce_code'])) {
            return (string)$commerceCodes[$first]['commerce_code'];
        }

        throw new LocalizedException(__('No default commerce code found in configuration'));
    }
}
