<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Propultech\WebpayPlusMallRest\Api\Data\WebpayMallOrderDataInterface;
use Propultech\WebpayPlusMallRest\Api\WebpayMallOrderDataRepositoryInterface;

class WebpayMallOrderDataRepository implements WebpayMallOrderDataRepositoryInterface
{
    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private ResourceConnection $resource
    )
    {
    }

    /**
     * @param $id
     * @return array|mixed
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');
        $select = $connection->select()->from($table)->where('id = ?', $id)->limit(1);
        $row = $connection->fetchRow($select) ?: [];
        if (!$row) {
            throw new NoSuchEntityException(__('Transaction with id %1 not found', $id));
        }
        return $row;
    }

    /**
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return WebpayMallOrderDataInterface
     * @throws NoSuchEntityException
     */
    public function save(WebpayMallOrderDataInterface $webpayMallOrderData)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');

        $data = [
            'order_id'          => substr((string)$webpayMallOrderData->getOrderId(), 0, 60),
            'buy_order'         => substr((string)$webpayMallOrderData->getBuyOrder(), 0, 20),
            'child_buy_order'   => substr((string)$webpayMallOrderData->getChildBuyOrder(), 0, 20),
            'commerce_codes'    => (string)$webpayMallOrderData->getCommerceCodes(),
            'child_commerce_code' => substr((string)$webpayMallOrderData->getChildCommerceCode(), 0, 60),
            'amount'            => (int)$webpayMallOrderData->getAmount(),
            'token'             => substr((string)$webpayMallOrderData->getToken(), 0, 100),
            'transbank_status'  => (string)$webpayMallOrderData->getTransbankStatus(),
            'session_id'        => substr((string)$webpayMallOrderData->getSessionId(), 0, 20),
            'quote_id'          => substr((string)$webpayMallOrderData->getQuoteId(), 0, 20),
            'payment_status'    => substr((string)$webpayMallOrderData->getPaymentStatus(), 0, 30),
            'metadata'          => (string)$webpayMallOrderData->getMetadata(),
            'product'           => substr((string)$webpayMallOrderData->getProduct(), 0, 50),
            'environment'       => substr((string)$webpayMallOrderData->getEnvironment(), 0, 50),
        ];

        // Remove null values to let DB defaults apply
        $data = array_filter($data, static fn($v) => $v !== null);

        $id = $webpayMallOrderData->getId();
        if ($id) {
            $affected = $connection->update($table, $data, ['id = ?' => (int)$id]);
            if ($affected === 0) {
                throw new NoSuchEntityException(__('Cannot update transaction with id %1 because it does not exist', $id));
            }
        } else {
            $connection->insert($table, $data);
            $id = (int)$connection->lastInsertId($table);
            // Try to set the ID back on the provided object (ignore if immutable)
            try {
                $webpayMallOrderData->setId($id);
            } catch (\Throwable $e) {
                // silently ignore
            }
        }

        return $webpayMallOrderData;
    }

    /**
     * @param $orderId
     * @param $quoteId
     * @return array|array[]
     */
    public function getByOrderIdAndQuoteId($orderId, $quoteId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');
        $select = $connection->select()
            ->from($table)
            ->where('order_id = ?', $orderId)
            ->where('quote_id = ?', $quoteId)
            ->order('id ASC');
        $rows = $connection->fetchAll($select) ?: [];
        return $rows;
    }

    /**
     * @param $token
     * @return array|array[]
     */
    public function getByToken($token)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');
        $select = $connection->select()->from($table)->where('token = ?', $token)->order('id ASC');
        $rows = $connection->fetchAll($select) ?: [];
        return $rows;
    }

    /**
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return true
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function delete(WebpayMallOrderDataInterface $webpayMallOrderData)
    {
        $id = (int)($webpayMallOrderData->getId() ?? 0);
        if (!$id) {
            throw new LocalizedException(__('Cannot delete transaction without ID'));
        }
        return $this->deleteById($id);
    }

    /**
     * @param $id
     * @return true
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');
        $affected = $connection->delete($table, ['id = ?' => (int)$id]);
        if ($affected === 0) {
            throw new NoSuchEntityException(__('Transaction with id %1 not found', $id));
        }
        return true;
    }

    /**
     * @param $orderId
     * @return array|array[]
     */
    public function getByOrderId($orderId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('webpay_mall_order_data');
        $select = $connection->select()->from($table)->where('order_id = ?', $orderId)->order('id ASC');
        $rows = $connection->fetchAll($select) ?: [];
        return $rows;
    }
}
