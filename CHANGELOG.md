# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.0] - 2025-06-06
### Added
- Initial release of Webpay Plus Mall integration for Magento 2
- Support for multiple commerce codes
- Product attribute to differentiate products by commerce code
- Transaction details builder for creating mall transactions
- Automatic invoice generation (optional)
- Customizable order status handling
- Documentation in README.md

## [0.0.1] - 2025-06-06
### Changed
- Updated PHP version requirement to `^7.4.0 || ^8.1.0` for compatibility with PHP 8.1
- Updated Magento framework requirement to `^103.0.0` for compatibility with Magento 2.4.6+
- Specified minimum version for Transbank Webpay Magento 2 REST dependency (`^2.3`)

### Improved
- Added property type declarations throughout the module
- Added method parameter type declarations
- Added method return type declarations
- Used strict comparison operators (`===`, `!==`) instead of loose ones (`==`, `!=`)
- Added proper null handling with null coalescing operator (`??`)
- Replaced direct instantiation of classes with proper dependency injection
- Added factory classes for complex object creation
- Removed usage of ObjectManager where possible
- Injected logger instances instead of creating them in constructors
- Added more specific exception handling with appropriate exception types
- Added validation for required parameters
- Added proper error logging with exception type information
- Added null checks before accessing object properties
- Added try-catch blocks around critical operations
- Extracted common code into separate methods for better maintainability
- Changed protected properties to private for better encapsulation
- Added proper PHPDoc comments for all methods and properties
- Used repository pattern for data access instead of direct model loading
- Added validation for input parameters
- Added proper type casting for numeric values
- Added checks for empty values before using them
- Added validation for configuration parameters
- Optimized database queries by using repositories and search criteria
- Reduced unnecessary object creation
- Added early returns to avoid unnecessary processing

### Added
- Created IMPROVEMENTS.md file documenting all improvements

## [0.0.2] - 2025-06-06
### Changed
- Converted product attribute 'webpay_mall_commerce_code' from text field to dropdown
- Updated attribute to use CommerceCode source model for populating dropdown options

### Added
- Added CommerceCode source model to provide options from system configuration
- Added dynamic row configuration for commerce codes in system configuration
- Added documentation for commerce codes feature in COMMERCE_CODES.md
- Added test script for verifying commerce codes configuration

## [0.0.3] - 2025-06-06
### Changed
- Refactored constructors to use promoted properties (PHP 8.1 feature)
- Converted all applicable classes to use promoted properties constructors

### Added
- Added documentation for promoted properties in PROMOTED_PROPERTIES.md
- Added test script for verifying promoted properties functionality

## [0.0.4] - 2025-06-06
### Changed
- Migrated from InstallData and UpgradeData scripts to Data Patches
- Removed InstallData.php and UpgradeData.php
- Created AddWebpayMallCommerceCodeAttribute data patch
- Created UpdateWebpayMallCommerceCodeAttribute data patch

### Added
- Added documentation for data patches migration in DATA_PATCHES.md
- Added test script for verifying data patches functionality

## [0.0.5] - 2025-06-06
### Added
- Added CHANGELOG.md file to track all changes to the project

## [0.0.6] - 2025-06-06
### Added
- Replace payment title with Webpay Plus logo in the frontend template for improved branding.
- Add custom CSS to adjust logo size and styling for better visual integration.
- Introduce SVG logo asset for higher fidelity and scalability.


## [Released]
