<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Propultech_WebpayPlusMallRest::transactions';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context                      $context,
        private readonly PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Propultech_WebpayPlusMallRest::transactions');
        $resultPage->getConfig()->getTitle()->prepend(__('Webpay Plus Mall Transactions'));
        return $resultPage;
    }
}
