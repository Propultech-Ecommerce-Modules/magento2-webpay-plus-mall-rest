<?php

namespace Propultech\WebpayPlusMallRest\Model;

class WebpayPlusMall extends \Magento\Payment\Model\Method\AbstractMethod
{
    public const CODE = 'propultech_webpayplusmall';
    public const PRODUCT_NAME = 'webpay_plus_mall';

    /**
     * Payment code.
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Array of currency support.
     */
    protected $_supportedCurrencyCodes = ['CLP'];

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canAuthorize = true;

    /**
     * Availability for currency.
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }

        return true;
    }
}
