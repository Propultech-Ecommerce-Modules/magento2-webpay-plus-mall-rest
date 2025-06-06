<?php
/**
 * Test script for Propultech_WebpayPlusMallRest commerce codes
 *
 * This script tests the commerce codes configuration and the product attribute dropdown.
 * To run this script, execute the following command from the Magento root directory:
 * php -f app/code/Propultech/WebpayPlusMallRest/Test/test_commerce_codes.php
 */

// Bootstrap Magento
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Test 1: Check if the module is enabled
$moduleManager = $objectManager->get(\Magento\Framework\Module\Manager::class);
$isModuleEnabled = $moduleManager->isEnabled('Propultech_WebpayPlusMallRest');
echo "Module Propultech_WebpayPlusMallRest is " . ($isModuleEnabled ? "enabled" : "disabled") . "\n";

// Test 2: Check if the product attribute exists and is a dropdown
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'webpay_mall_commerce_code');
$isAttributeExists = $attribute && $attribute->getId();
echo "Product attribute webpay_mall_commerce_code is " . ($isAttributeExists ? "created" : "not created") . "\n";

if ($isAttributeExists) {
    echo "Attribute input type: " . $attribute->getFrontendInput() . "\n";
    echo "Attribute source model: " . $attribute->getSourceModel() . "\n";

    // Test 3: Check if the source model returns options
    $sourceModel = $objectManager->create($attribute->getSourceModel());
    $options = $sourceModel->getAllOptions();
    echo "Number of options in dropdown: " . count($options) . "\n";

    if (count($options) > 0) {
        echo "Options:\n";
        foreach ($options as $option) {
            echo "  - " . $option['label'] . " (value: " . $option['value'] . ")\n";
        }
    }
}

// Test 4: Check the commerce codes configuration
$scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$commerceCodesJson = $scopeConfig->getValue(
    'payment/propultech_webpayplusmall/commerce_codes',
    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
);

echo "\nCommerce codes configuration:\n";
echo $commerceCodesJson . "\n";

if (!empty($commerceCodesJson)) {
    $commerceCodes = json_decode($commerceCodesJson, true);
    if (is_array($commerceCodes)) {
        echo "Number of commerce codes configured: " . count($commerceCodes) . "\n";
        foreach ($commerceCodes as $index => $row) {
            echo "  " . ($index + 1) . ". ";
            if (isset($row['commerce_name']) && isset($row['commerce_code'])) {
                echo $row['commerce_name'] . " - " . $row['commerce_code'] . "\n";
            } else {
                echo "Invalid configuration row\n";
            }
        }
    } else {
        echo "Invalid commerce codes configuration format\n";
    }
} else {
    echo "No commerce codes configured\n";
}

echo "\nTest completed.\n";
