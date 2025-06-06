<?php
/**
 * Verification script for Propultech_WebpayPlusMallRest data patches
 *
 * This script checks if the product attribute created by data patches exists and is configured correctly.
 * To run this script, execute the following command from the Magento root directory:
 * php -f app/code/Propultech/WebpayPlusMallRest/Test/verify_data_patches.php
 */

// Bootstrap Magento
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

echo "Verifying Data Patches for Propultech_WebpayPlusMallRest module\n";
echo "============================================================\n\n";

// Check if module is enabled
$moduleManager = $objectManager->get(\Magento\Framework\Module\Manager::class);
$isModuleEnabled = $moduleManager->isEnabled('Propultech_WebpayPlusMallRest');
echo "Module Propultech_WebpayPlusMallRest is " . ($isModuleEnabled ? "enabled" : "disabled") . "\n\n";

// Check if the product attribute exists
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'webpay_mall_commerce_code');
$isAttributeExists = $attribute && $attribute->getId();
echo "Product attribute webpay_mall_commerce_code is " . ($isAttributeExists ? "created" : "not created") . "\n";

if ($isAttributeExists) {
    // Check attribute properties
    echo "Attribute properties:\n";
    echo "- Label: " . $attribute->getFrontendLabel() . "\n";
    echo "- Input type: " . $attribute->getFrontendInput() . "\n";
    echo "- Source model: " . $attribute->getSourceModel() . "\n";
    echo "- Note: " . $attribute->getNote() . "\n";

    // Verify that it's a dropdown
    $isDropdown = $attribute->getFrontendInput() === 'select';
    echo "Attribute is " . ($isDropdown ? "correctly" : "not") . " configured as a dropdown\n";

    // Verify source model
    $correctSourceModel = $attribute->getSourceModel() === 'Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode';
    echo "Attribute has " . ($correctSourceModel ? "correct" : "incorrect") . " source model\n";

    // Check if the source model returns options
    if ($correctSourceModel) {
        $sourceModel = $objectManager->create($attribute->getSourceModel());
        $options = $sourceModel->getAllOptions();
        echo "Source model returns " . count($options) . " options\n";
    }
}

// Check if data patches have been applied
$patchHistory = $objectManager->get(\Magento\Framework\Setup\Patch\PatchHistory::class);
$addPatchApplied = $patchHistory->isApplied('Propultech\WebpayPlusMallRest\Setup\Patch\Data\AddWebpayMallCommerceCodeAttribute');
$updatePatchApplied = $patchHistory->isApplied('Propultech\WebpayPlusMallRest\Setup\Patch\Data\UpdateWebpayMallCommerceCodeAttribute');

echo "\nData patch status:\n";
echo "- AddWebpayMallCommerceCodeAttribute: " . ($addPatchApplied ? "Applied" : "Not applied") . "\n";
echo "- UpdateWebpayMallCommerceCodeAttribute: " . ($updatePatchApplied ? "Applied" : "Not applied") . "\n";

echo "\nVerification completed.\n";
