<?php

namespace Propultech\WebpayPlusMallRest\Api\Data;

interface WebpayMallOrderDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID = 'id';
    const ORDER_ID = 'order_id';
    const BUY_ORDER = 'buy_order';
    const CHILD_BUY_ORDER = 'child_buy_order';
    const COMMERCE_CODES = 'commerce_codes';
    const CHILD_COMMERCE_CODE = 'child_commerce_code';
    const AMOUNT = 'amount';
    const TOKEN = 'token';
    const TRANSBANK_STATUS = 'transbank_status';
    const SESSION_ID = 'session_id';
    const QUOTE_ID = 'quote_id';
    const PAYMENT_STATUS = 'payment_status';
    const METADATA = 'metadata';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const PRODUCT = 'product';
    const ENVIRONMENT = 'environment';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get Order ID
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set Order ID
     *
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get Buy Order
     *
     * @return string|null
     */
    public function getBuyOrder();

    /**
     * Set Buy Order
     *
     * @param string $buyOrder
     * @return $this
     */
    public function setBuyOrder($buyOrder);

    /**
     * Get Child Buy Order
     *
     * @return string|null
     */
    public function getChildBuyOrder();

    /**
     * Set Child Buy Order
     *
     * @param string $childBuyOrder
     * @return $this
     */
    public function setChildBuyOrder($childBuyOrder);

    /**
     * Get Commerce Codes
     *
     * @return string|null
     */
    public function getCommerceCodes();

    /**
     * Set Commerce Codes
     *
     * @param string $commerceCodes
     * @return $this
     */
    public function setCommerceCodes($commerceCodes);

    /**
     * Get Child Commerce Code
     *
     * @return string|null
     */
    public function getChildCommerceCode();

    /**
     * Set Child Commerce Code
     *
     * @param string $childCommerceCode
     * @return $this
     */
    public function setChildCommerceCode($childCommerceCode);

    /**
     * Get Amount
     *
     * @return int|null
     */
    public function getAmount();

    /**
     * Set Amount
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Get Token
     *
     * @return string|null
     */
    public function getToken();

    /**
     * Set Token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token);

    /**
     * Get Transbank Status
     *
     * @return string|null
     */
    public function getTransbankStatus();

    /**
     * Set Transbank Status
     *
     * @param string $transbankStatus
     * @return $this
     */
    public function setTransbankStatus($transbankStatus);

    /**
     * Get Session ID
     *
     * @return string|null
     */
    public function getSessionId();

    /**
     * Set Session ID
     *
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId($sessionId);

    /**
     * Get Quote ID
     *
     * @return string|null
     */
    public function getQuoteId();

    /**
     * Set Quote ID
     *
     * @param string $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Get Payment Status
     *
     * @return string|null
     */
    public function getPaymentStatus();

    /**
     * Set Payment Status
     *
     * @param string $paymentStatus
     * @return $this
     */
    public function setPaymentStatus($paymentStatus);

    /**
     * Get Metadata
     *
     * @return string|null
     */
    public function getMetadata();

    /**
     * Set Metadata
     *
     * @param string $metadata
     * @return $this
     */
    public function setMetadata($metadata);

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get Product
     *
     * @return string|null
     */
    public function getProduct();

    /**
     * Set Product
     *
     * @param string $product
     * @return $this
     */
    public function setProduct($product);

    /**
     * Get Environment
     *
     * @return string|null
     */
    public function getEnvironment();

    /**
     * Set Environment
     *
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment);
}
