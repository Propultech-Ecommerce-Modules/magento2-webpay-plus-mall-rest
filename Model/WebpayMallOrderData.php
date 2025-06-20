<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Propultech\WebpayPlusMallRest\Api\Data\WebpayMallOrderDataInterface;

class WebpayMallOrderData extends AbstractModel implements WebpayMallOrderDataInterface, IdentityInterface
{
    const CACHE_TAG = 'propultech_webpay_mall_order_data';
    const PAYMENT_STATUS_WAITING = 'WAITING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILED = 'FAILED';
    const PAYMENT_STATUS_CANCELED_BY_USER = 'CANCELED_BY_USER';
    const PAYMENT_STATUS_ERROR = 'ERROR';
    const PAYMENT_STATUS_TIMEOUT = 'TIMEOUT';
    const PAYMENT_STATUS_NULLIFIED = 'NULLIFIED';
    const PAYMENT_STATUS_REVERSED = 'REVERSED';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData::class);
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Order ID
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set Order ID
     *
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get Buy Order
     *
     * @return string|null
     */
    public function getBuyOrder()
    {
        return $this->getData(self::BUY_ORDER);
    }

    /**
     * Set Buy Order
     *
     * @param string $buyOrder
     * @return $this
     */
    public function setBuyOrder($buyOrder)
    {
        return $this->setData(self::BUY_ORDER, $buyOrder);
    }

    /**
     * Get Child Buy Order
     *
     * @return string|null
     */
    public function getChildBuyOrder()
    {
        return $this->getData(self::CHILD_BUY_ORDER);
    }

    /**
     * Set Child Buy Order
     *
     * @param string $childBuyOrder
     * @return $this
     */
    public function setChildBuyOrder($childBuyOrder)
    {
        return $this->setData(self::CHILD_BUY_ORDER, $childBuyOrder);
    }

    /**
     * Get Commerce Codes
     *
     * @return string|null
     */
    public function getCommerceCodes()
    {
        return $this->getData(self::COMMERCE_CODES);
    }

    /**
     * Set Commerce Codes
     *
     * @param string $commerceCodes
     * @return $this
     */
    public function setCommerceCodes($commerceCodes)
    {
        return $this->setData(self::COMMERCE_CODES, $commerceCodes);
    }

    /**
     * Get Child Commerce Code
     *
     * @return string|null
     */
    public function getChildCommerceCode()
    {
        return $this->getData(self::CHILD_COMMERCE_CODE);
    }

    /**
     * Set Child Commerce Code
     *
     * @param string $childCommerceCode
     * @return $this
     */
    public function setChildCommerceCode($childCommerceCode)
    {
        return $this->setData(self::CHILD_COMMERCE_CODE, $childCommerceCode);
    }

    /**
     * Get Amount
     *
     * @return int|null
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set Amount
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Get Token
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->getData(self::TOKEN);
    }

    /**
     * Set Token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        return $this->setData(self::TOKEN, $token);
    }

    /**
     * Get Transbank Status
     *
     * @return string|null
     */
    public function getTransbankStatus()
    {
        return $this->getData(self::TRANSBANK_STATUS);
    }

    /**
     * Set Transbank Status
     *
     * @param string $transbankStatus
     * @return $this
     */
    public function setTransbankStatus($transbankStatus)
    {
        return $this->setData(self::TRANSBANK_STATUS, $transbankStatus);
    }

    /**
     * Get Session ID
     *
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * Set Session ID
     *
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * Get Quote ID
     *
     * @return string|null
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * Set Quote ID
     *
     * @param string $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * Get Payment Status
     *
     * @return string|null
     */
    public function getPaymentStatus()
    {
        return $this->getData(self::PAYMENT_STATUS);
    }

    /**
     * Set Payment Status
     *
     * @param string $paymentStatus
     * @return $this
     */
    public function setPaymentStatus($paymentStatus)
    {
        return $this->setData(self::PAYMENT_STATUS, $paymentStatus);
    }

    /**
     * Get Metadata
     *
     * @return string|null
     */
    public function getMetadata()
    {
        return $this->getData(self::METADATA);
    }

    /**
     * Set Metadata
     *
     * @param string $metadata
     * @return $this
     */
    public function setMetadata($metadata)
    {
        return $this->setData(self::METADATA, $metadata);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get Product
     *
     * @return string|null
     */
    public function getProduct()
    {
        return $this->getData(self::PRODUCT);
    }

    /**
     * Set Product
     *
     * @param string $product
     * @return $this
     */
    public function setProduct($product)
    {
        return $this->setData(self::PRODUCT, $product);
    }

    /**
     * Get Environment
     *
     * @return string|null
     */
    public function getEnvironment()
    {
        return $this->getData(self::ENVIRONMENT);
    }

    /**
     * Set Environment
     *
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        return $this->setData(self::ENVIRONMENT, $environment);
    }
}
