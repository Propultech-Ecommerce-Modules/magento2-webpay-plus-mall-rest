# Promoted Properties in Propultech_WebpayPlusMallRest

This document explains the use of promoted properties constructors in the Propultech_WebpayPlusMallRest module.

## What are Promoted Properties?

Promoted properties are a feature introduced in PHP 8.1 that allows you to define and initialize class properties directly in the constructor parameter list. This reduces boilerplate code and makes the code more concise and readable.

### Traditional Way (Before PHP 8.1)

```php
class Example
{
    private $property;
    
    public function __construct($property)
    {
        $this->property = $property;
    }
}
```

### With Promoted Properties (PHP 8.1+)

```php
class Example
{
    public function __construct(
        private $property
    ) {
    }
}
```

## Benefits of Using Promoted Properties

1. **Reduced Boilerplate**: Eliminates the need for separate property declarations and constructor assignments.
2. **Improved Readability**: Makes the code more concise and easier to read.
3. **Less Error-Prone**: Reduces the chance of errors from mismatched property names or forgotten assignments.
4. **Better Maintainability**: Easier to add, remove, or modify dependencies.

## Implementation in Propultech_WebpayPlusMallRest

The following classes in the Propultech_WebpayPlusMallRest module have been updated to use promoted properties:

1. **CommerceCode.php**
   - Converted `$scopeConfig` property to a promoted property

2. **TransbankSdkWebpayPlusMallRest.php**
   - Converted `$log` property to a promoted property
   - Note: `$mallTransaction` property could not be converted because it's initialized with a new instance, not a constructor parameter

3. **TransactionDetailsBuilder.php**
   - Converted `$productRepository` and `$scopeConfig` properties to promoted properties

4. **ConfigProvider.php**
   - Converted `$scopeConfig` and `$urlBuilder` properties to promoted properties
   - Maintained `protected` visibility for these properties

5. **Create.php**
   - Converted all properties to promoted properties while maintaining the parent constructor call

6. **Commit.php**
   - Converted all properties to promoted properties while maintaining the parent constructor call

7. **InstallData.php**
   - Converted `$eavSetupFactory` property to a promoted property

8. **UpgradeData.php**
   - Converted `$eavSetupFactory` property to a promoted property

## Testing

A test script has been created to verify that the classes with promoted properties constructors work correctly:

```bash
php -f app/code/Propultech/WebpayPlusMallRest/Test/test_promoted_properties.php
```

This script tests the instantiation and basic functionality of the modified classes.

## Requirements

- PHP 8.1 or higher
- Magento 2.4.6 or higher

## Conclusion

The use of promoted properties constructors in the Propultech_WebpayPlusMallRest module has resulted in more concise, readable, and maintainable code while maintaining the same functionality. This is part of our ongoing effort to modernize the codebase and take advantage of new PHP features.
