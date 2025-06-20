<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Propultech\WebpayPlusMallRest\Api\WebpayMallOrderDataRepositoryInterface;
use Propultech\WebpayPlusMallRest\Api\Data\WebpayMallOrderDataInterface;
use Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData as WebpayMallOrderDataResource;
use Propultech\WebpayPlusMallRest\Model\ResourceModel\WebpayMallOrderData\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Class WebpayMallOrderDataRepository
 * Repository for WebpayMallOrderData model
 */
class WebpayMallOrderDataRepository implements WebpayMallOrderDataRepositoryInterface
{
    /**
     * @var WebpayMallOrderDataResource
     */
    protected $resource;

    /**
     * @var WebpayMallOrderDataFactory
     */
    protected $webpayMallOrderDataFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param WebpayMallOrderDataResource $resource
     * @param WebpayMallOrderDataFactory $webpayMallOrderDataFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        WebpayMallOrderDataResource $resource,
        WebpayMallOrderDataFactory $webpayMallOrderDataFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->webpayMallOrderDataFactory = $webpayMallOrderDataFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Save WebpayMallOrderData
     *
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return WebpayMallOrderDataInterface
     * @throws CouldNotSaveException
     */
    public function save(WebpayMallOrderDataInterface $webpayMallOrderData)
    {
        try {
            $this->resource->save($webpayMallOrderData);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $webpayMallOrderData;
    }

    /**
     * Get WebpayMallOrderData by ID
     *
     * @param int $id
     * @return WebpayMallOrderDataInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $webpayMallOrderData = $this->webpayMallOrderDataFactory->create();
        $this->resource->load($webpayMallOrderData, $id);
        if (!$webpayMallOrderData->getId()) {
            throw new NoSuchEntityException(__('WebpayMallOrderData with id "%1" does not exist.', $id));
        }
        return $webpayMallOrderData;
    }

    /**
     * Get WebpayMallOrderData by Order ID and Quote ID
     *
     * @param string $orderId
     * @param string $quoteId
     * @return WebpayMallOrderDataInterface
     */
    public function getByOrderIdAndQuoteId($orderId, $quoteId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId);

        return $collection->getFirstItem();
    }

    /**
     * Get WebpayMallOrderData by Token
     *
     * @param string $token
     * @return WebpayMallOrderDataInterface
     */
    public function getByToken($token)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('token', $token);

        return $collection->getFirstItem();
    }

    /**
     * Delete WebpayMallOrderData
     *
     * @param WebpayMallOrderDataInterface $webpayMallOrderData
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(WebpayMallOrderDataInterface $webpayMallOrderData)
    {
        try {
            $this->resource->delete($webpayMallOrderData);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete WebpayMallOrderData by ID
     *
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
