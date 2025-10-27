<?php

namespace Propultech\WebpayPlusMallRest\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Propultech\WebpayPlusMallRest\Api\Data\WebpayMallOrderDataInterface;

/**
 * Interface WebpayMallOrderDataRepositoryInterface
 * @api
 */
interface WebpayMallOrderDataRepositoryInterface
{
    /**
     * Save WebpayMallOrderData
     *
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return WebpayMallOrderDataInterface
     * @throws LocalizedException
     */
    public function save(WebpayMallOrderDataInterface $webpayMallOrderData);

    /**
     * Get WebpayMallOrderData by ID
     *
     * @param int $id
     * @return array Array representing the transaction row
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get WebpayMallOrderData rows by Order ID and Quote ID
     *
     * @param string $orderId
     * @param string $quoteId
     * @return array[] List of rows (each row as an associative array)
     */
    public function getByOrderIdAndQuoteId($orderId, $quoteId);

    /**
     * Get WebpayMallOrderData rows by Order ID
     *
     * @param string $orderId
     * @return array[] List of rows (each row as an associative array)
     */
    public function getByOrderId($orderId);

    /**
     * Get WebpayMallOrderData rows by Token
     *
     * @param string $token
     * @return array[] List of rows (each row as an associative array)
     */
    public function getByToken($token);

    /**
     * Delete WebpayMallOrderData
     *
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return bool
     * @throws LocalizedException
     */
    public function delete(WebpayMallOrderDataInterface $webpayMallOrderData);

    /**
     * Delete WebpayMallOrderData by ID
     *
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);
}
