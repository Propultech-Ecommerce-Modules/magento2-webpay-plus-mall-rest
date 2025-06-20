<?php

namespace Propultech\WebpayPlusMallRest\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WebpayMallOrderData extends AbstractDb
{
    const TABLE_NAME = 'webpay_mall_order_data';

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }
}
