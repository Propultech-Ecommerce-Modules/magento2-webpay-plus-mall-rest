<?php
/**
 * Test script for Propultech_WebpayPlusMallRest global adjustments
 *
 * This script tests the distribution of global discounts and charges among commerce code groups.
 * To run this script, execute the following command from the Magento root directory:
 * php -f app/code/Propultech/WebpayPlusMallRest/Test/test_global_adjustments.php
 */

// Bootstrap Magento
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

echo "Testing global adjustments distribution in TransactionDetailsBuilder\n";
echo "==================================================================\n\n";

// Get the TransactionDetailsBuilder
$transactionDetailsBuilder = $objectManager->create(\Propultech\WebpayPlusMallRest\Model\TransactionDetailsBuilder::class);

// Create a mock order with items and global adjustments
$mockOrder = createMockOrder($objectManager);

// Test the build method
$details = $transactionDetailsBuilder->build($mockOrder);

// Display the results
echo "Order Grand Total: " . $mockOrder->getGrandTotal() . "\n";
echo "Items Total: " . calculateItemsTotal($mockOrder) . "\n";
echo "Global Adjustment: " . ($mockOrder->getGrandTotal() - calculateItemsTotal($mockOrder)) . "\n\n";

echo "Transaction Details:\n";
foreach ($details as $detail) {
    echo "Commerce Code: " . $detail['commerce_code'] . "\n";
    echo "Buy Order: " . $detail['buy_order'] . "\n";
    echo "Amount: " . $detail['amount'] . "\n";
    echo "Installments: " . $detail['installments_number'] . "\n\n";
}

echo "Total Amount in Details: " . array_sum(array_column($details, 'amount')) . "\n";
echo "Should match Order Grand Total: " . $mockOrder->getGrandTotal() . "\n";
echo "Difference: " . (array_sum(array_column($details, 'amount')) - $mockOrder->getGrandTotal()) . "\n";

/**
 * Create a mock order with items and global adjustments
 *
 * @param \Magento\Framework\ObjectManagerInterface $objectManager
 * @return \Magento\Sales\Model\Order
 */
function createMockOrder($objectManager)
{
    // Create mock order
    $order = $objectManager->create(\Magento\Sales\Model\Order::class);

    // Set order ID and grand total (including global discount)
    $order->setId(12345);
    $order->setGrandTotal(9500); // 10000 - 500 (global discount)

    // Create mock items with different commerce codes
    $items = [];

    // Item 1 - Commerce Code A - 6000
    $item1 = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $item1->setProductId(1);
    $item1->setRowTotalInclTax(6000);
    $product1 = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $product1->setId(1);
    $product1->setData('webpay_mall_commerce_code', 'commerce_code_A');

    // Item 2 - Commerce Code B - 4000
    $item2 = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $item2->setProductId(2);
    $item2->setRowTotalInclTax(4000);
    $product2 = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $product2->setId(2);
    $product2->setData('webpay_mall_commerce_code', 'commerce_code_B');

    $items[] = $item1;
    $items[] = $item2;

    // Set up product repository mock to return our mock products
    $productRepository = $objectManager->create(
        \Magento\Catalog\Api\ProductRepositoryInterface::class,
        ['products' => [$product1, $product2]]
    );

    // Override the getById method to return our mock products
    $productRepository = new class($productRepository, [$product1, $product2]) implements \Magento\Catalog\Api\ProductRepositoryInterface {
        private $originalRepository;
        private $products;

        public function __construct($originalRepository, $products) {
            $this->originalRepository = $originalRepository;
            $this->products = [];
            foreach ($products as $product) {
                $this->products[$product->getId()] = $product;
            }
        }

        public function getById($productId, $editMode = false, $storeId = null, $forceReload = false) {
            if (isset($this->products[$productId])) {
                return $this->products[$productId];
            }
            return $this->originalRepository->getById($productId, $editMode, $storeId, $forceReload);
        }

        // Implement other methods from the interface
        public function save(\Magento\Catalog\Api\Data\ProductInterface $product, $saveOptions = false) { return $this->originalRepository->save($product, $saveOptions); }
        public function get($sku, $editMode = false, $storeId = null, $forceReload = false) { return $this->originalRepository->get($sku, $editMode, $storeId, $forceReload); }
        public function delete(\Magento\Catalog\Api\Data\ProductInterface $product) { return $this->originalRepository->delete($product); }
        public function deleteById($sku) { return $this->originalRepository->deleteById($sku); }
        public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria) { return $this->originalRepository->getList($searchCriteria); }
    };

    // Replace the product repository in the object manager
    $objectManager->configure([
        \Magento\Catalog\Api\ProductRepositoryInterface::class => ['shared' => true]
    ]);
    $objectManager->addSharedInstance($productRepository, \Magento\Catalog\Api\ProductRepositoryInterface::class);

    // Set up order to return our mock items
    $order->setItems($items);

    return $order;
}

/**
 * Calculate the total of all items in the order
 *
 * @param \Magento\Sales\Model\Order $order
 * @return float
 */
function calculateItemsTotal($order)
{
    $total = 0;
    foreach ($order->getAllItems() as $item) {
        $total += $item->getRowTotalInclTax();
    }
    return $total;
}
