# Changelog

All notable changes to AB Bricks Auto Token will be documented in this file.

## [1.0.0] - 2025-11-29

### Added
- Initial release of AB Bricks Auto Token plugin
- Automatic generation of Bricks Builder Dynamic Tokens from ACF and MetaBox fields
- Automatic generation of Bricks Builder Conditions from ACF and MetaBox fields
- Field naming convention: `field_name__post_type__token__condition`
- Support for Advanced Custom Fields (ACF) integration
- Support for MetaBox integration
- Modular integration system allowing external plugins to register custom integrations
- Admin instructions page with Basic Usage and Developer Guide tabs
- Plugin action link for easy access to instructions
- Comprehensive documentation in BRICKS_AUTO.md

### Features
- Dynamic token creation with pattern `{post_type_field_name}`
- Conditional logic support with customizable comparison operators
- Post type-specific grouping in Bricks UI
- Automatic field discovery on page load
- Context-aware field value rendering
- Support for boolean fields (true/false, checkbox) with simplified operators
- Support for text fields with full comparison operators (equals, not equals, contains, empty, not empty)

### Technical
- PSR-4 autoloading with Composer
- Interface-based integration architecture (IntegrationInterface)
- Base integration abstract class for shared functionality
- Integration registry for managing multiple field plugins
- Hook timing optimized for Bricks theme (`after_setup_theme` with priority 100)
- Proper Bricks filter implementation for dynamic tags and conditions

### Documentation
- Field naming pattern examples and usage instructions
- Developer guide for creating custom integrations
- Critical implementation details for Bricks integration
- Troubleshooting guide for common issues

[1.0.0]: https://github.com/wpeasy/ab-bricks-auto-token/releases/tag/1.0.0
