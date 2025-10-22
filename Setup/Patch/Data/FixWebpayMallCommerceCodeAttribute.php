<?php

namespace Propultech\WebpayPlusMallRest\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode;

class FixWebpayMallCommerceCodeAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface     $moduleDataSetup,
        private readonly EavSetupFactory              $eavSetupFactory,
        private readonly AttributeRepositoryInterface $attributeRepository
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Remove old attribute (if created without backend)
        if ($this->attributeExists('webpay_mall_commerce_code')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'webpay_mall_commerce_code');
        }

        // Recreate: select with source for UI, but store raw value to accept CSV values directly
        $eavSetup->addAttribute(
            Product::ENTITY,
            'webpay_mall_commerce_code',
            [
                'type' => 'varchar',
                'backend' => ArrayBackend::class,
                'frontend' => '',
                'label' => 'Webpay Mall Commerce Code',
                'input' => 'select',
                'class' => '',
                'source' => CommerceCode::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'note' => 'Select the commerce code to use for this product in Webpay Plus Mall transactions',
            ]
        );

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    private function attributeExists(string $code): bool
    {
        try {
            $this->attributeRepository->get(Product::ENTITY, $code);
            return true;
        } catch (NoSuchEntityException) {
            return false;
        }
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        // Ensure this runs after the original attribute patch, so it can fix/replace it.
        return [AddWebpayMallCommerceCodeAttribute::class];
    }
}
