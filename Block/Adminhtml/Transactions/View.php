<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Block\Adminhtml\Transactions;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Propultech\WebpayPlusMallRest\Model\WebpayMallOrderDataRepository;

class View extends Template
{
    protected $_template = 'Propultech_WebpayPlusMallRest::transactions/view.phtml';

    public function __construct(
        Context $context,
        private RequestInterface $request,
        private WebpayMallOrderDataRepository $repository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTransaction(): array
    {
        $id = (int)$this->request->getParam('id');
        if (!$id) {
            throw new NoSuchEntityException(__('Transaction not found'));
        }
        return $this->repository->getById($id);
    }
}
