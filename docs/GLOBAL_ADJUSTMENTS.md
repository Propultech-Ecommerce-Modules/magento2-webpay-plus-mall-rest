# Global Discounts and Charges in Webpay Plus Mall

This document explains how the Webpay Plus Mall module handles global discounts and charges applied to orders.

## Overview

In e-commerce transactions, there are often global adjustments applied to the entire order rather than to specific items. These can include:

- Cart-level discounts (e.g., coupon codes, promotional discounts)
- Shipping costs
- Handling fees
- Tax adjustments
- Other surcharges or discounts

When processing payments through Webpay Plus Mall, these global adjustments need to be distributed among the different commerce codes involved in the transaction.

## Implementation

The `TransactionDetailsBuilder` class has been enhanced to automatically handle global discounts and charges by:

1. Calculating the total amount of all items in the order
2. Comparing this total to the order's grand total to determine the global adjustment
3. Distributing this adjustment proportionally among the commerce code groups based on their relative amounts

### Example

Consider an order with the following details:

- Item 1: $6,000 (Commerce Code A)
- Item 2: $4,000 (Commerce Code B)
- Items Total: $10,000
- Global Discount: $500
- Order Grand Total: $9,500

The global discount of $500 will be distributed as follows:

- Commerce Code A: $6,000 - ($6,000 / $10,000 * $500) = $6,000 - $300 = $5,700
- Commerce Code B: $4,000 - ($4,000 / $10,000 * $500) = $4,000 - $200 = $3,800

The transaction details will include:
```
[
  {
    "commerce_code": "commerce_code_A",
    "buy_order": "200000{order_id}_0",
    "amount": 5700,
    "installments_number": 1
  },
  {
    "commerce_code": "commerce_code_B",
    "buy_order": "200000{order_id}_1",
    "amount": 3800,
    "installments_number": 1
  }
]
```

The sum of these amounts ($5,700 + $3,800 = $9,500) matches the order's grand total.

## Handling Rounding Issues

To avoid rounding issues that might cause the sum of the distributed amounts to differ slightly from the order's grand total, the implementation:

1. Calculates the proportional adjustment for each commerce code group
2. Keeps track of the total adjustment applied so far
3. For the last commerce code group, applies the remaining adjustment (the difference between the total global adjustment and the adjustment applied so far)

This ensures that the sum of all amounts in the transaction details exactly matches the order's grand total.

## Testing

A test script is provided to verify the correct distribution of global adjustments:

```bash
php -f app/code/Propultech/WebpayPlusMallRest/Test/test_global_adjustments.php
```

This script creates a mock order with items assigned to different commerce codes and a global discount, then verifies that:

1. The global discount is correctly calculated
2. The discount is proportionally distributed among the commerce code groups
3. The sum of all amounts in the transaction details matches the order's grand total

## Benefits

This implementation ensures that:

1. Global discounts and charges are fairly distributed among all commerce codes
2. The total amount charged to the customer matches the order's grand total
3. Each commerce code receives the correct proportion of the payment
4. Rounding issues are handled properly
