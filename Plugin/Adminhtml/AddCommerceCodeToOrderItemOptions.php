<?php

namespace Propultech\WebpayPlusMallRest\Plugin\Adminhtml;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer;
use Psr\Log\LoggerInterface;

class AddCommerceCodeToOrderItemOptions
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Append Webpay Mall commerce code under SKU in admin order items view
     *
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions(DefaultRenderer $subject, $result)
    {
        try {
            $item = $subject->getItem();
            if (!$item) {
                return $result;
            }

            $productId = (int)$item->getProductId();
            if ($productId <= 0) {
                return $result;
            }

            $product = $this->productRepository->getById($productId);
            $commerceCode = (string)$product->getData('webpay_mall_commerce_code');
            $commerceCode = trim($commerceCode);

            if ($commerceCode !== '') {
                if (!is_array($result)) {
                    $result = [];
                }
                $result[] = [
                    'label' => __('Ccommerce Code'),
                    'value' => $commerceCode,
                ];
            }
        } catch (\Throwable $e) {
            // Don't break admin rendering if anything goes wrong
            if (isset($this->logger)) {
                $this->logger->error('[WebpayPlusMallRest] Error appending commerce code to admin order item options: ' . $e->getMessage());
            }
        }

        return $result;
    }
}
