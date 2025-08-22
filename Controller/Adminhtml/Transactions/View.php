<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class View extends Action
{
    public const ADMIN_RESOURCE = 'Propultech_WebpayPlusMallRest::transactions';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute(): Page|Redirect
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Missing transaction identifier.'));
            /** @var Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('propultech_webpayplusmall/transactions/index');
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Propultech_WebpayPlusMallRest::transactions');
        $resultPage->getConfig()->getTitle()->prepend(__('Webpay Plus Mall Transaction #%1', $id));
        return $resultPage;
    }
}
