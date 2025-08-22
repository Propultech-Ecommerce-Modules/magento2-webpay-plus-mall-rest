<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TransactionActions extends Column
{
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        private UrlInterface $urlBuilder,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['id'])) {
                    $viewUrl = $this->urlBuilder->getUrl(
                        'propultech_webpayplusmall/transactions/view',
                        ['id' => $item['id']]
                    );
                    $item[$this->getData('name')] = [
                        'view' => [
                            'href' => $viewUrl,
                            'label' => __('View')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
