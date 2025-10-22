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

        // Group items by commerce code (sum of item rows including tax)
        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById((int)$item->getProductId());
                $commerceCode = $product->getData('webpay_mall_commerce_code') ?? $defaultCommerceCode;

                if (!isset($commerceCodeGroups[$commerceCode])) {
                    $commerceCodeGroups[$commerceCode] = 0.0;
                }
                $rowTotalInclTax = (float)$item->getRowTotalInclTax() - $item->getDiscountAmount();
                $commerceCodeGroups[$commerceCode] += $rowTotalInclTax;
            } catch (\Exception $e) {
                // If a product cannot be loaded, skip the item but keep building the rest
                continue;
            }
        }

        // If we couldn't group any item by commerce code, fallback to default commerce code with full grand total
        $grandTotal = (float)$order->getGrandTotal();
        if (empty($commerceCodeGroups)) {
            if (!empty($defaultCommerceCode)) {
                return [[
                    'commerce_code' => $defaultCommerceCode,
                    'buy_order' => $buyOrderPrefix . $order->getId() . '_0',
                    'amount' => (int)round($grandTotal)
                ]];
            }
            // If there is no default code either, this is a configuration problem
            throw new LocalizedException(__('No commerce codes available to build transaction details'));
        }

        // Calculate proportional distribution of order-level adjustments (discounts, shipping, handling, etc.)
        // We compute: adjustment = grand_total - sum(item_rows_incl_tax)
        $sumItems = 0.0;
        foreach ($commerceCodeGroups as $cc => $subtotal) {
            $sumItems += (float)$subtotal;
        }

        $adjustment = (float)round($grandTotal) - (int)round($sumItems); // work in integer cents (CLP has no cents but we keep int)

        // Prepare structures for allocation
        $groups = [];
        foreach ($commerceCodeGroups as $commerceCode => $subtotal) {
            $groups[] = [
                'commerce_code' => $commerceCode,
                'subtotal' => (float)$subtotal,
                'base' => (int)round((float)$subtotal),
                'shareFloat' => 0.0,
                'shareInt' => 0,
                'frac' => 0.0,
            ];
        }

        $n = count($groups);
        if ($n === 0) {
            // Safety (shouldn't happen due to early return), but handle just in case
            if (!empty($defaultCommerceCode)) {
                return [[
                    'commerce_code' => $defaultCommerceCode,
                    'buy_order' => $buyOrderPrefix . $order->getId() . '_0',
                    'amount' => (int)round($grandTotal)
                ]];
            }
            throw new LocalizedException(__('No commerce codes available to build transaction details'));
        }

        if ((int)$sumItems !== 0) {
            foreach ($groups as $idx => $g) {
                $shareFloat = ($adjustment) * ($g['subtotal'] / $sumItems);
                $groups[$idx]['shareFloat'] = $shareFloat;
                $shareInt = (int)floor($shareFloat);
                $groups[$idx]['shareInt'] = $shareInt;
                $groups[$idx]['frac'] = $shareFloat - $shareInt; // in [0,1)
            }
        } else {
            // When items sum to zero, distribute evenly
            $even = $n > 0 ? ($adjustment / $n) : 0.0;
            foreach ($groups as $idx => $g) {
                $shareFloat = $even;
                $groups[$idx]['shareFloat'] = $shareFloat;
                $shareInt = (int)floor($shareFloat);
                $groups[$idx]['shareInt'] = $shareInt;
                $groups[$idx]['frac'] = $shareFloat - $shareInt;
            }
        }

        // Distribute remainder caused by flooring to match exact integer adjustment
        $allocated = 0;
        foreach ($groups as $g) {
            $allocated += $g['shareInt'];
        }
        $remainder = (int)$adjustment - (int)$allocated; // remainder >= 0
        if ($remainder !== 0) {
            // Sort by descending fractional part
            usort($groups, function ($a, $b) {
                if ($a['frac'] === $b['frac']) return 0;
                return ($a['frac'] < $b['frac']) ? 1 : -1;
            });
            for ($k = 0; $k < $remainder && $k < count($groups); $k++) {
                $groups[$k]['shareInt'] += 1;
            }
            // Restore original order is not strictly needed; we'll iterate groups to build details regardless
        }

        // Build final details and validate sums
        $details = [];
        $i = 0;
        $detailsSum = 0;
        foreach ($groups as $g) {
            $amount = (int)$g['base'] + (int)$g['shareInt'];
            if (empty($g['commerce_code'])) {
                // Skip empty commerce codes
                continue;
            }
            $details[] = [
                'commerce_code' => $g['commerce_code'],
                'buy_order' => $buyOrderPrefix . $order->getIncrementId() . '_' . $i,
                'amount' => $amount,
            ];
            $detailsSum += $amount;
            $i++;
        }

        $expected = (int)round($grandTotal);
        if ($detailsSum !== $expected) {
            throw new LocalizedException(__('Transaction details total (%1) does not match order total (%2).', $detailsSum, $expected));
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
