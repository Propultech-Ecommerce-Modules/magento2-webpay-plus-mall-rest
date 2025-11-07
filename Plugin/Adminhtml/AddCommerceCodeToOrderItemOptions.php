<?php

namespace Propultech\WebpayPlusMallRest\Plugin\Adminhtml;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode;
use Psr\Log\LoggerInterface;

class AddCommerceCodeToOrderItemOptions
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param Escaper $escaper
     * @param CommerceCode $commerceCodeSource
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface            $logger,
        private readonly Escaper                    $escaper,
        private readonly CommerceCode               $commerceCodeSource
    )
    {
    }

    /**
     * After plugin to append Webpay Mall commerce code into the 'product' column HTML
     * of the admin sales order view items renderer.
     *
     * @param DefaultRenderer $subject
     * @param string $result
     * @param DataObject $item
     * @param string $column
     * @param string|null $field
     * @return string
     */
    public function afterGetColumnHtml(DefaultRenderer $subject, $result, DataObject $item, $column, $field = null)
    {
        try {
            if ($column !== 'product') {
                return $result;
            }

            // Retrieve commerce code from product attribute
            $productId = (int)($item->getData('product_id') ?? $item->getProductId());
            if ($productId <= 0) {
                return $result;
            }

            $product = $this->productRepository->getById($productId);
            $code = trim((string)$product->getData('webpay_mall_commerce_code'));

            // Resolve label (commerce name) from attribute source; fallback to code if empty
            $label = $product->getAttributeText('webpay_mall_commerce_code');
            $label = trim(is_array($label) ? implode(', ', $label) : (string)$label);
            if (empty($code)) {
                try {
                    $options = $this->commerceCodeSource->getAllOptions();
                    if (is_array($options) && !empty($options)) {
                        $firstLabel = $options[1]['label'];
                        $label = $firstLabel !== '' ? $firstLabel : $code;
                    } else {
                        $label = $code;
                    }
                } catch (\Throwable $e) {
                    $label = $code;
                }
            }

            // Build the snippet to append right after the product name and before closing container
            $ccHtml = '<div class="webpay-mall-commerce-code">'
                . $this->escaper->escapeHtml(__('Commerce Name')) . ': ' . $this->escaper->escapeHtml($label)
                . '</div>';

            // If the renderer wrapped the product column in a container, inject before the last closing </div>
            if ($subject->canDisplayContainer()) {
                $pos = strripos((string)$result, '</div>');
                if ($pos !== false) {
                    $result = substr($result, 0, $pos) . $ccHtml . substr($result, $pos);
                    return $result;
                }
            }

            // Fallback: simply append
            $result .= $ccHtml;
            return $result;
        } catch (\Throwable $e) {
            // Never break admin UI due to plugin failure
            if (isset($this->logger)) {
                $this->logger->error('[WebpayPlusMallRest] Error appending commerce code to product column: ' . $e->getMessage());
            }
            return $result;
        }
    }
}
