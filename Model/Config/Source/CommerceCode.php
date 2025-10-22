<?php

namespace Propultech\WebpayPlusMallRest\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider;

class CommerceCode extends AbstractSource
{
    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        private ConfigProvider $configProvider
    ) {
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = $this->getCommerceCodeOptions();
        }
        return $this->_options;
    }

    /**
     * Get commerce code options from system configuration via ConfigProvider
     *
     * @return array
     */
    private function getCommerceCodeOptions()
    {
        $options = [];

        // Add empty option
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];

        $commerceCodes = $this->configProvider->getCommerceCodes();
        if (is_array($commerceCodes)) {
            foreach ($commerceCodes as $row) {
                if (isset($row['commerce_name']) && isset($row['commerce_code'])) {
                    $options[] = [
                        'value' => $row['commerce_code'],
                        'label' => $row['commerce_name'] . ' (' . $row['commerce_code'] . ')'
                    ];
                }
            }
        }

        return $options;
    }
}
