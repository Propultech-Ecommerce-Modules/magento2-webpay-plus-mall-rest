<?php

namespace Propultech\WebpayPlusMallRest\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->updateWebpayMallCommerceCodeAttribute($setup);
        }

        $setup->endSetup();
    }

    /**
     * Update webpay_mall_commerce_code attribute to be a dropdown
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function updateWebpayMallCommerceCodeAttribute(ModuleDataSetupInterface $setup)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->updateAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'webpay_mall_commerce_code',
            [
                'input' => 'select',
                'source' => 'Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode',
                'note' => 'Select the commerce code to use for this product in Webpay Plus Mall transactions'
            ]
        );
    }
}
