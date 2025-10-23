<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;
use Psr\Log\LoggerInterface;

class TransactionDetailsBuilder
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigProvider $configProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ConfigProvider             $configProvider,
        private readonly LoggerInterface            $logger
    )
    {
    }

    /**
     * Build transaction details array for Webpay Plus Mall
     *
     * @param Order $order
     * @param string $buyOrderPrefix
     * @return array
     * @throws LocalizedException
     */
    public function build(Order $order, string $buyOrderPrefix = ''): array
    {
        $orderId = (string)$order->getIncrementId();
        $grandTotal = (float)$order->getGrandTotal();
        $items = $order->getAllItems();
        $this->logger->logInfo('Building Webpay Mall transaction details', [
            'order' => $orderId,
            'itemsCount' => count($items),
            'grandTotal' => (int)round($grandTotal)
        ]);

        $commerceCodeGroups = [];
        $defaultCommerceCode = $this->getDefaultChildCommerceCode();
        $this->logger->logInfo('Default child commerce code resolved', ['code' => $defaultCommerceCode]);
        // Group items by commerce code (sum of item rows including tax)
        foreach ($items as $item) {
            try {
                $productId = (int)$item->getProductId();
                $product = $this->productRepository->getById($productId);
                $commerceCode = $product->getData('webpay_mall_commerce_code') ?? $defaultCommerceCode;

                if (!isset($commerceCodeGroups[$commerceCode])) {
                    $commerceCodeGroups[$commerceCode] = 0.0;
                }
                $rowTotalInclTax = (float)$item->getRowTotalInclTax() - (float)$item->getDiscountAmount();
                $commerceCodeGroups[$commerceCode] += $rowTotalInclTax;
            } catch (\Exception $e) {
                // If a product cannot be loaded, skip the item but keep building the rest
                $this->logger->logError('Failed to load product while grouping items for transaction details', [
                    'order' => $orderId,
                    'itemId' => $item->getItemId(),
                    'productId' => $item->getProductId(),
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // Always compute and assign shipping to the configured shipping commerce code (fallback to default)
        $shippingInclTax = (float)$order->getShippingInclTax();
        $shippingDiscount = (float)$order->getShippingDiscountAmount();
        $shippingNet = $shippingInclTax - $shippingDiscount; // net shipping charged to customer
        $shippingNetInt = (int)round($shippingNet);
        $shippingCommerceCode = $this->getShippingCommerceCode();
        $chosenShippingCode = !empty($shippingCommerceCode) ? $shippingCommerceCode : $defaultCommerceCode;
        $this->logger->logInfo('Computed shipping amounts', [
            'order' => $orderId,
            'shipping_incl_tax' => (int)round($shippingInclTax),
            'shipping_discount' => (int)round($shippingDiscount),
            'shipping_net_int' => $shippingNetInt,
            'shipping_commerce_code_config' => $shippingCommerceCode,
            'chosen_shipping_commerce_code' => $chosenShippingCode,
            'default_commerce_code' => $defaultCommerceCode
        ]);
        if (!empty($chosenShippingCode) && $shippingNetInt !== 0) {
            if (!isset($commerceCodeGroups[$chosenShippingCode])) {
                $commerceCodeGroups[$chosenShippingCode] = 0.0;
            }
            $commerceCodeGroups[$chosenShippingCode] += (float)$shippingNetInt;
            $this->logger->logInfo('Assigned shipping net to commerce code group', [
                'order' => $orderId,
                'shipping_commerce_code' => $chosenShippingCode,
                'added_shipping' => $shippingNetInt
            ]);
        }

        // If we couldn't group any item by commerce code and shipping didn't create a group, fallback to default commerce code with full grand total
        if (empty($commerceCodeGroups)) {
            if (!empty($defaultCommerceCode)) {
                $this->logger->logInfo('No items grouped by commerce code. Falling back to default commerce code for full total', [
                    'order' => $orderId,
                    'defaultCommerceCode' => $defaultCommerceCode,
                    'amount' => (int)round($grandTotal)
                ]);
                return [[
                    'commerce_code' => $defaultCommerceCode,
                    'buy_order' => $buyOrderPrefix . $order->getId() . '_0',
                    'amount' => (int)round($grandTotal)
                ]];
            }
            // If there is no default code either, this is a configuration problem
            $this->logger->logError('No commerce codes available to build transaction details (empty groups and no default)', [
                'order' => $orderId
            ]);
            throw new LocalizedException(__('No commerce codes available to build transaction details'));
        }

        // Calculate proportional distribution of order-level adjustments (discounts, handling, etc.)
        // We compute: adjustment = grand_total - sum(grouped_subtotals) where grouped subtotals already include shipping (if any)
        $sumItems = 0.0;
        foreach ($commerceCodeGroups as $cc => $subtotal) {
            $sumItems += (float)$subtotal;
        }
        $this->logger->logInfo('Grouped subtotals by commerce code (includes shipping if allocated)', [
            'order' => $orderId,
            'groups' => $commerceCodeGroups,
            'sumItemsRounded' => (int)round($sumItems)
        ]);

        $adjustment = (int)round($grandTotal) - (int)round($sumItems); // integer units (CLP)
        $this->logger->logInfo('Computed order-level adjustment to distribute', [
            'order' => $orderId,
            'grandTotalRounded' => (int)round($grandTotal),
            'sumItemsRounded' => (int)round($sumItems),
            'shippingNetInt' => $shippingNetInt,
            'adjustment' => (int)$adjustment
        ]);

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
                $this->logger->logError('Safety fallback reached: no groups after grouping, using default code', [
                    'order' => $orderId,
                    'defaultCommerceCode' => $defaultCommerceCode
                ]);
                return [[
                    'commerce_code' => $defaultCommerceCode,
                    'buy_order' => $buyOrderPrefix . $order->getId() . '_0',
                    'amount' => (int)round($grandTotal)
                ]];
            }
            $this->logger->logError('No commerce codes available to build transaction details (safety check)', [
                'order' => $orderId
            ]);
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
        $remainder = (int)$adjustment - (int)$allocated;
        if ($remainder !== 0) {
            // Sort by descending fractional part
            usort($groups, function ($a, $b) {
                if ($a['frac'] === $b['frac']) return 0;
                return ($a['frac'] < $b['frac']) ? 1 : -1;
            });
            for ($k = 0; $k < $remainder && $k < count($groups); $k++) {
                $groups[$k]['shareInt'] += 1;
            }
        }

        $this->logger->logInfo('Allocation after distributing adjustment and remainder', [
            'order' => $orderId,
            'groups' => array_map(function ($g) {
                return [
                    'commerce_code' => $g['commerce_code'],
                    'base' => $g['base'],
                    'shareInt' => $g['shareInt']
                ];
            }, $groups),
            'adjustment' => (int)$adjustment
        ]);

        // Build final details and validate sums
        $details = [];
        $i = 0;
        $detailsSum = 0;
        foreach ($groups as $g) {
            $amount = (int)$g['base'] + (int)$g['shareInt'];
            if (empty($g['commerce_code'])) {
                // Skip empty commerce codes
                $this->logger->logError('Encountered empty commerce code during details build, skipping', [
                    'order' => $orderId,
                    'group' => $g
                ]);
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
            $this->logger->logError('Transaction details sum does not match order total', [
                'order' => $orderId,
                'detailsSum' => (int)$detailsSum,
                'expected' => (int)$expected,
                'details' => $details
            ]);
            throw new LocalizedException(__('Transaction details total (%1) does not match order total (%2).', $detailsSum, $expected));
        }

        $this->logger->logInfo('Built Webpay Mall transaction details successfully', [
            'order' => $orderId,
            'details' => $details,
            'total' => $detailsSum
        ]);

        return $details;
    }

    /**
     * Get default child commerce code from configuration via ConfigProvider
     *
     * @return string
     * @throws LocalizedException
     */
    private function getDefaultChildCommerceCode(): string
    {
        $commerceCodes = $this->configProvider->getCommerceCodes();
        if (!is_array($commerceCodes) || empty($commerceCodes)) {
            $this->logger->logError('No default commerce code found in configuration');
            throw new LocalizedException(__('No default commerce code found in configuration'));
        }
        $firstKey = array_key_first($commerceCodes);
        $firstRow = $commerceCodes[$firstKey] ?? null;
        if (is_array($firstRow) && !empty($firstRow['commerce_code'])) {
            return (string)$firstRow['commerce_code'];
        }
        $this->logger->logError('No default commerce code found in configuration (missing commerce_code key)');
        throw new LocalizedException(__('No default commerce code found in configuration'));
    }

    /**
     * Get shipping commerce code from configuration (optional)
     */
    private function getShippingCommerceCode(): string
    {
        return trim((string)$this->configProvider->getShippingCommerceCode());
    }
}
