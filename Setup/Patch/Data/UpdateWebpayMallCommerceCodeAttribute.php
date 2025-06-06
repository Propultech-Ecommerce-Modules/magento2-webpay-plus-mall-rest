<?php

namespace Propultech\WebpayPlusMallRest\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

class UpdateWebpayMallCommerceCodeAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            Product::ENTITY,
            'webpay_mall_commerce_code',
            [
                'input' => 'select',
                'source' => 'Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode',
                'note' => 'Select the commerce code to use for this product in Webpay Plus Mall transactions'
            ]
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            AddWebpayMallCommerceCodeAttribute::class
        ];
    }
}
