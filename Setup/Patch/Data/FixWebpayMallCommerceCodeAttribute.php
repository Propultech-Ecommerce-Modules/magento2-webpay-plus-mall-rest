<?php

namespace Propultech\WebpayPlusMallRest\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixWebpayMallCommerceCodeAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        private readonly ModuleDataSetupInterface   $moduleDataSetup,
        private readonly EavSetupFactory            $eavSetupFactory,
        private readonly AttributeRepositoryInterface $attributeRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Ensure idempotency: remove existing attribute if it already exists
        if ($this->attributeExists('webpay_mall_commerce_code')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'webpay_mall_commerce_code');
        }

        // Recreate attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'webpay_mall_commerce_code',
            [
                'type' => 'varchar',
                // store raw value (so CSV import saves provided code directly)
                'backend' => ArrayBackend::class,
                'frontend' => '',
                'label' => 'Webpay Mall Commerce Code',
                'input' => 'select',
                'class' => '',
                'source' => 'Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode',
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

    /**
     * Check if attribute exists for product entity.
     */
    private function attributeExists(string $attributeCode): bool
    {
        try {
            /** @var AttributeInterface $attr */
            $attr = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
            return (bool)$attr->getAttributeId();
        } catch (NoSuchEntityException $e) {
            return false;
        }
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
        // If this patch must run after other setup data patches, list them here.
        // Example:
        // return [\Vendor\Module\Setup\Patch\Data\SomeDependencyPatch::class];
        return [AddWebpayMallCommerceCodeAttribute::class];
    }
}
