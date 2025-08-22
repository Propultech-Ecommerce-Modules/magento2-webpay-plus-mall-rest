<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Propultech_WebpayPlusMallRest::transactions';

    public function __construct(
        Context $context,
        private PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Propultech_WebpayPlusMallRest::transactions');
        $resultPage->getConfig()->getTitle()->prepend(__('Webpay Plus Mall Transactions'));
        return $resultPage;
    }
}
