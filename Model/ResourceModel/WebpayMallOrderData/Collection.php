<?php

namespace Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Propultech\WebpayPlusMallRest\Model\WebpayMallOrderData;
use Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData as WebpayMallOrderDataResource;

/**
 * Class Collection
 * Collection for WebpayMallOrderData model
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            WebpayMallOrderData::class,
            WebpayMallOrderDataResource::class
        );
    }
}
