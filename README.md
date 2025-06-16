# Webpay Plus Mall Integration for Magento 2

This module provides integration with Transbank's Webpay Plus Mall payment gateway for Magento 2. It allows customers to pay for orders using Webpay Plus Mall, which supports multiple commerce codes for different stores.

## Features

- Integration with Transbank's Webpay Plus Mall payment gateway
- Support for multiple commerce codes
- Product attribute to differentiate products by commerce code
- Transaction details builder for creating mall transactions
- Automatic invoice generation (optional)
- Customizable order status handling

## Requirements

- Magento 2.3.x or higher
- PHP 7.3 or higher
- Transbank SDK 2.0 or higher
- Transbank Webpay Magento 2 REST module

## Installation

1. Copy the module files to `app/code/Propultech/WebpayPlusMallRest` in your Magento installation.
2. Enable the module:
   ```bash
   bin/magento module:enable Propultech_WebpayPlusMallRest
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   ```

## Configuration

1. Go to **Stores > Configuration > Sales > Payment Methods > Webpay Plus Mall**.
2. Configure the following settings:
   - **Enabled**: Set to "Yes" to enable the payment method.
   - **Title**: The title of the payment method shown to customers.
   - **Environment**: Select "TEST" for testing or "PRODUCTION" for live transactions.
   - **Código de Comercio Mall**: Your Webpay Plus Mall commerce code.
   - **API Key**: Your Webpay Plus Mall API key.
   - **Códigos de Comercio de Tiendas**: Enter the commerce codes for your stores in JSON format. Example:
     ```json
     [
       {
         "store_code": "store1",
         "commerce_code": "597055555541"
       },
       {
         "store_code": "store2",
         "commerce_code": "597055555542"
       }
     ]
     ```
   - **Estado de Pago Exitoso**: The order status for successful payments.
   - **Estado de Pago Erroneo**: The order status for failed payments.
   - **Estado de Nueva Orden**: The order status for new orders.
   - **Posición del plugin**: The position of the payment method in the checkout.
   - **Comportamiento del Email**: When to send order confirmation emails.
   - **Generar Invoice**: Whether to automatically generate invoices for successful payments.

## Product Configuration

1. Edit a product and go to the **General** tab.
2. Find the **Webpay Mall Commerce Code** field.
3. Enter the commerce code to use for this product in Webpay Plus Mall transactions.
4. If left empty, the first commerce code from the configuration will be used.

## How It Works

1. When a customer places an order using Webpay Plus Mall, the module creates a transaction with Transbank.
2. The customer is redirected to Webpay's payment page to complete the payment.
3. After payment, the customer is redirected back to the store.
4. The module verifies the payment and updates the order status accordingly.
5. If configured, an invoice is automatically generated for the order.

## Transaction Details Builder

The module includes a transaction details builder that groups products by their associated commerce code and builds the transaction details array for Webpay Plus Mall. This allows for flexible handling of multiple commerce codes in a single transaction.

### Global Discounts and Charges

The transaction details builder automatically handles global discounts and charges applied to the order. These global adjustments (such as cart-level discounts, shipping costs, or other fees) are distributed proportionally among the different commerce code groups based on their relative amounts. This ensures that:

1. Each commerce code group receives its fair share of any global discount or charge
2. The sum of all amounts in the transaction details exactly matches the order's grand total
3. Rounding issues are avoided by applying any remaining adjustment to the last commerce code group

## Support

For support, please contact the module developer or refer to the Transbank documentation:
- [Transbank Developers](https://www.transbankdevelopers.cl/)
- [Webpay Plus Mall Documentation](https://www.transbankdevelopers.cl/producto/webpay#webpay-plus-mall)
