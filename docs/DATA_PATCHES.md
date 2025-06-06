# Migration to Data Patches

This document explains the migration from InstallData and UpgradeData scripts to Data Patches in the Propultech_WebpayPlusMallRest module.

## Overview

In Magento 2.3.0 and later, the recommended way to install and upgrade data is to use data patches instead of the older InstallData and UpgradeData scripts. Data patches provide a more flexible and maintainable way to manage data changes in your module.

## Changes Made

The following changes were made to migrate from the old setup scripts to data patches:

1. Created two data patch classes:
   - `AddWebpayMallCommerceCodeAttribute` - Creates the product attribute
   - `UpdateWebpayMallCommerceCodeAttribute` - Updates the attribute to be a dropdown

2. Removed the old setup scripts:
   - `InstallData.php`
   - `UpgradeData.php`

3. Updated the module.xml file to remove the setup_version attribute (this was already done).

## Benefits of Data Patches

- **Atomic Changes**: Each data patch represents a single, atomic change to the database.
- **Dependencies**: Data patches can declare dependencies on other patches, ensuring they are applied in the correct order.
- **Idempotence**: Data patches are applied only once, even if the setup:upgrade command is run multiple times.
- **Maintainability**: Data patches are easier to maintain and understand than the old setup scripts.
- **No Version Tracking**: Data patches don't require version tracking in the module.xml file.

## How Data Patches Work

Data patches are PHP classes that implement the `DataPatchInterface` interface. They have an `apply()` method that contains the logic to be executed. The `getAliases()` method returns an array of aliases for the patch, and the `getDependencies()` method returns an array of other patches that this patch depends on.

When the `setup:upgrade` command is run, Magento checks which patches have been applied and applies any new patches in the correct order based on their dependencies.

## Testing

A verification script has been created to check if the data patches have been applied correctly:

```bash
php -f app/code/Propultech/WebpayPlusMallRest/Test/verify_data_patches.php
```

This script checks if the 'webpay_mall_commerce_code' attribute exists, if it's configured as a dropdown, if it has the correct source model, and if the data patches have been applied.

## References

- [Magento DevDocs: Data Patches](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/declarative-schema/data-patches.html)
- [Magento DevDocs: Declarative Schema](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/declarative-schema/)
