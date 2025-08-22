<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

class WebpayMallOrderDataRepository
{
    public function __construct(private ResourceConnection $resource)
    {
    }

    public function getById(int $id): array
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
}
