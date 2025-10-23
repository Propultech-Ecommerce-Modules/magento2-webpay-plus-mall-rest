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
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode;

class RecreateWebpayMallCommerceCodeAttribute implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface     $moduleDataSetup,
        private readonly EavSetupFactory              $eavSetupFactory,
        private readonly AttributeRepositoryInterface $attributeRepository
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
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
                'label' => 'Webpay Mall Commerce Code',
                'input' => 'select',
                'source' => CommerceCode::class,
                'frontend' => '',
                'required' => false,
                'backend' => ArrayBackend::class,
                'sort_order' => '99',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'note' => 'Select the commerce code to use for this product in Webpay Plus Mall transactions',
                'used_in_product_listing' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => ''
            ]
        );

        $this->moduleDataSetup->endSetup();
    }

    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'webpay_mall_commerce_code');

        $this->moduleDataSetup->endSetup();
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
