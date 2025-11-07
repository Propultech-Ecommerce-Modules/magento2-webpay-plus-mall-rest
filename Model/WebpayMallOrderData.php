<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Framework\Model\AbstractModel;

class WebpayMallOrderData extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData::class);
    }
}
