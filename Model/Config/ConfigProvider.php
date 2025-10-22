<?php
declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected UrlInterface         $urlBuilder
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'propultech_webpayplusmall' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('propultech_webpayplusmall/transaction/create'),
                    'title' => $this->getTitle()
                ]
            ]
        ];
    }

    /**
     * Get payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/title',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get plugin configuration
     *
     * @return array
     */
    public function getPluginConfig()
    {
        return [
            'ENVIRONMENT' => $this->scopeConfig->getValue('payment/propultech_webpayplusmall/environment', ScopeInterface::SCOPE_WEBSITES),
            'COMMERCE_CODE' => $this->scopeConfig->getValue('payment/propultech_webpayplusmall/commerce_code', ScopeInterface::SCOPE_WEBSITES),
            'API_KEY' => $this->scopeConfig->getValue('payment/propultech_webpayplusmall/api_key', ScopeInterface::SCOPE_WEBSITES),
            'URL_RETURN' => 'propultech_webpayplusmall/transaction/commit',
        ];
    }

    /**
     * Get plugin configuration
     *
     * @return array
     */
    public function getTbkPluginConfig()
    {
        return [
            'ENVIRONMENT' => $this->scopeConfig->getValue('payment/transbank_webpay/environment', ScopeInterface::SCOPE_WEBSITES),
            'COMMERCE_CODE' => $this->scopeConfig->getValue('payment/transbank_webpay/commerce_code', ScopeInterface::SCOPE_WEBSITES),
            'API_KEY' => $this->scopeConfig->getValue('payment/transbank_webpay/api_key', ScopeInterface::SCOPE_WEBSITES),
            'URL_RETURN' => 'transbank_webpay/transaction/commit',
        ];
    }

    /**
     * Get commerce codes from configuration
     *
     * @return array
     */
    public function getCommerceCodes()
    {
        $commerceCodesJson = $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/commerce_codes',
            ScopeInterface::SCOPE_WEBSITES
        );

        $commerceCodes = json_decode($commerceCodesJson, true);

        if (!empty($commerceCodes) && is_array($commerceCodes)) {
            return $commerceCodes;
        }

        return [];
    }

    /**
     * Get order success status
     *
     * @return string
     */
    public function getOrderSuccessStatus()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/payment_successful_status',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get order error status
     *
     * @return string
     */
    public function getOrderErrorStatus()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/payment_error_status',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get new order status
     *
     * @return string
     */
    public function getOrderPendingStatus()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/new_order_status',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get email behavior
     *
     * @return string
     */
    public function getEmailBehavior()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/new_email_order',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get invoice settings
     *
     * @return string
     */
    public function getInvoiceSettings()
    {
        return $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/invoice_settings',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get shipping commerce code to which shipping must be allocated
     */
    public function getShippingCommerceCode(): string
    {
        $code = (string)$this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/shipping_commerce_code',
            ScopeInterface::SCOPE_WEBSITES
        );
        return trim($code);
    }
}
