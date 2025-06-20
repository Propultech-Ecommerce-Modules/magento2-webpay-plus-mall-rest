<?php

namespace Propultech\WebpayPlusMallRest\Api;

use Propultech\WebpayPlusMallRest\Api\Data\WebpayMallOrderDataInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @return WebpayMallOrderDataInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get WebpayMallOrderData by Order ID and Quote ID
     *
     * @param string $orderId
     * @param string $quoteId
     * @return WebpayMallOrderDataInterface
     */
    public function getByOrderIdAndQuoteId($orderId, $quoteId);

    /**
     * Get WebpayMallOrderData by Token
     *
     * @param string $token
     * @return WebpayMallOrderDataInterface
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
