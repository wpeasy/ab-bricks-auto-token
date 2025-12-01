# Changelog

All notable changes to AB Bricks Auto Token will be documented in this file.

## [1.0.2] - 2025-11-30

### Fixed
- **Critical**: Fixed cache contamination bug where ACF and MetaBox shared static cache property
- Fixed duplicate token detection causing false positives and missing tokens
- Resolved issue where only duplicate "Extra Image" tokens appeared in Bricks dropdown
- Each integration now maintains its own separate cache preventing cross-contamination

### Technical
- Added individual `$fields_cache` property override in both `ACFIntegration` and `MetaBoxIntegration` classes
- Implemented final deduplication pass ensuring unique token names and conditional keys
- Enhanced ACF meta box filtering in MetaBox integration to prevent field discovery overlap
- Added unique key tracking during field discovery loop to prevent duplicates
- Root cause: PHP static properties in parent class are shared across child classes unless explicitly overridden

### Impact
- All tokens from both ACF and MetaBox now display correctly in Bricks
- Cache system fully functional without workarounds
- Field discovery performance maintained with proper caching

## [1.0.1] - 2025-11-30

### Fixed
- Fixed image field rendering for Bricks Image Element
- Image context now returns array format `[attachment_id]` instead of bare integer
- Resolved "Dynamic data is empty" error when using image fields in Bricks Image Elements
- Resolved "Image ID (X) no longer exist" errors caused by Bricks treating integer as string

### Technical
- Updated `render_tag()` method in both ACF and MetaBox integrations
- Return format matches Bricks' expectation of array for dynamic data in image context
- Applied to all image field types: MetaBox (single_image, image_advanced, etc.) and ACF (image, gallery, file)

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
