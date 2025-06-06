# Commerce Codes Configuration for Webpay Plus Mall

This document explains how to configure and use the commerce codes feature in the Webpay Plus Mall module.

## Overview

The Webpay Plus Mall module allows you to configure multiple commerce codes for different stores or product types. Each product can be associated with a specific commerce code, which will be used when processing payments through Webpay Plus Mall.

## Configuration

### Step 1: Configure Commerce Codes

1. Go to **Stores > Configuration > Sales > Payment Methods > Webpay Plus Mall**.
2. In the **Códigos de Comercio de Tiendas** section, you can add multiple commerce codes:
   - **Commerce Name**: A descriptive name for the commerce (e.g., "Electronics Store", "Clothing Store")
   - **Commerce Code**: The actual commerce code provided by Transbank for this store

   ![Commerce Codes Configuration](../docs/images/commerce_codes_config.png)

3. Click **Add** to add more commerce codes as needed.
4. Click **Save Config** to save your changes.

### Step 2: Associate Products with Commerce Codes

When creating or editing a product:

1. Go to the **General** tab.
2. Find the **Webpay Mall Commerce Code** dropdown field.
3. Select the appropriate commerce code for this product from the dropdown.
4. Save the product.

![Product Commerce Code](../docs/images/product_commerce_code.png)

## How It Works

When a customer places an order:

1. The module checks each product in the cart for its associated commerce code.
2. Products with the same commerce code are grouped together.
3. The transaction is created with multiple details, one for each commerce code group.
4. Each detail includes the commerce code, a unique buy order, and the total amount for that group.

## Importing Products

When importing products, you can specify the Commerce Name in the import file. The module will automatically map this to the corresponding Commerce Code based on your configuration.

Example CSV import format:
```
sku,name,price,webpay_mall_commerce_code
ABC123,Test Product,100,Electronics Store
```

## Testing

To verify that the commerce codes are configured correctly, you can run the test script:

```bash
php -f app/code/Propultech/WebpayPlusMallRest/Test/test_commerce_codes.php
```

This script will check:
1. If the module is enabled
2. If the product attribute exists and is a dropdown
3. If the source model returns options
4. The commerce codes configuration

## Troubleshooting

If you encounter issues with the commerce codes:

1. Make sure you have run the setup:upgrade command after installing or updating the module:
   ```bash
   bin/magento setup:upgrade
   ```

2. Check that the commerce codes are properly configured in the system configuration.

3. Verify that products have the correct commerce code selected.

4. Check the Magento logs for any errors related to the module.
