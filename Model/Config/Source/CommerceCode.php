<?php

namespace Propultech\WebpayPlusMallRest\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CommerceCode extends AbstractSource
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
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
     * Get commerce code options from system configuration
     *
     * @return array
     */
    private function getCommerceCodeOptions()
    {
        $options = [];

        // Add empty option
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];

        // Get commerce codes from system configuration
        $commerceCodesJson = $this->scopeConfig->getValue(
            'payment/propultech_webpayplusmall/commerce_codes',
            ScopeInterface::SCOPE_STORE
        );

        if (!empty($commerceCodesJson)) {
            $commerceCodes = json_decode($commerceCodesJson, true);

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
        }

        return $options;
    }
}
