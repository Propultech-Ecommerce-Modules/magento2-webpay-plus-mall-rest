<?php
/**
 * Verification script for Propultech_WebpayPlusMallRest module
 *
 * This script checks if the module is registered with Magento and if the payment method is available.
 * To run this script, execute the following command from the Magento root directory:
 * php -f app/code/Propultech/WebpayPlusMallRest/Test/verify_module.php
 */

// Bootstrap Magento
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Check if module is enabled
$moduleManager = $objectManager->get(\Magento\Framework\Module\Manager::class);
$isModuleEnabled = $moduleManager->isEnabled('Propultech_WebpayPlusMallRest');
echo "Module Propultech_WebpayPlusMallRest is " . ($isModuleEnabled ? "enabled" : "disabled") . "\n";

// Check if payment method is available
$paymentHelper = $objectManager->get(\Magento\Payment\Helper\Data::class);
$paymentMethods = $paymentHelper->getPaymentMethods();
$isPaymentMethodAvailable = isset($paymentMethods['propultech_webpayplusmall']);
echo "Payment method propultech_webpayplusmall is " . ($isPaymentMethodAvailable ? "available" : "not available") . "\n";

// Check if product attribute exists
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'webpay_mall_commerce_code');
$isAttributeExists = $attribute && $attribute->getId();
echo "Product attribute webpay_mall_commerce_code is " . ($isAttributeExists ? "created" : "not created") . "\n";

// Check if config provider is registered
$configProviders = $objectManager->get(\Magento\Checkout\Model\CompositeConfigProvider::class);
$reflection = new ReflectionClass($configProviders);
$property = $reflection->getProperty('configProviders');
$property->setAccessible(true);
$providers = $property->getValue($configProviders);
$isConfigProviderRegistered = false;
foreach ($providers as $provider) {
    if ($provider instanceof \Propultech\WebpayPlusMallRest\Model\Config\ConfigProvider) {
        $isConfigProviderRegistered = true;
        break;
    }
}
echo "Config provider is " . ($isConfigProviderRegistered ? "registered" : "not registered") . "\n";

echo "\nVerification completed.\n";
