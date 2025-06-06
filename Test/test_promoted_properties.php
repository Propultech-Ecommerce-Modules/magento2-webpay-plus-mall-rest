<?php
/**
 * Test script for Propultech_WebpayPlusMallRest promoted properties
 *
 * This script tests that the classes with promoted properties constructors work correctly.
 * To run this script, execute the following command from the Magento root directory:
 * php -f app/code/Propultech/WebpayPlusMallRest/Test/test_promoted_properties.php
 */

// Bootstrap Magento
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

echo "Testing classes with promoted properties constructors...\n\n";

// Test 1: CommerceCode
try {
    $commerceCode = $objectManager->create(\Propultech\WebpayPlusMallRest\Model\Config\Source\CommerceCode::class);
    echo "✓ CommerceCode class instantiated successfully\n";

    // Test that the class works as expected
    $options = $commerceCode->getAllOptions();
    echo "  - getAllOptions() returned " . count($options) . " options\n";
} catch (\Exception $e) {
    echo "✗ Error instantiating CommerceCode: " . $e->getMessage() . "\n";
}

// Test 2: TransactionDetailsBuilder
try {
    $transactionDetailsBuilder = $objectManager->create(\Propultech\WebpayPlusMallRest\Model\TransactionDetailsBuilder::class);
    echo "✓ TransactionDetailsBuilder class instantiated successfully\n";
} catch (\Exception $e) {
    echo "✗ Error instantiating TransactionDetailsBuilder: " . $e->getMessage() . "\n";
}

// Test 3: ConfigProvider
try {
    $configProvider = $objectManager->create(\Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider::class);
    echo "✓ ConfigProvider class instantiated successfully\n";

    // Test that the class works as expected
    $config = $configProvider->getConfig();
    echo "  - getConfig() returned configuration for payment method\n";
} catch (\Exception $e) {
    echo "✗ Error instantiating ConfigProvider: " . $e->getMessage() . "\n";
}

// Test 4: TransbankSdkWebpayPlusMallRest
try {
    // We need to create a mock PluginLogger first
    $logger = $objectManager->create(\Transbank\Webpay\Helper\PluginLogger::class);

    // Create the TransbankSdkWebpayPlusMallRest with minimal config
    $config = ['ENVIRONMENT' => 'TEST'];
    $transbankSdk = $objectManager->create(
        \Propultech\WebpayPlusMallRest\Model\TransbankSdkWebpayPlusMallRest::class,
        [
            'log' => $logger,
            'config' => $config
        ]
    );
    echo "✓ TransbankSdkWebpayPlusMallRest class instantiated successfully\n";
} catch (\Exception $e) {
    echo "✗ Error instantiating TransbankSdkWebpayPlusMallRest: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed.\n";
