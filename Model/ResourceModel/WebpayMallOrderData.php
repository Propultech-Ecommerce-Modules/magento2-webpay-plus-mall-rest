<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WebpayMallOrderData extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('webpay_mall_order_data', 'id');
    }
}
