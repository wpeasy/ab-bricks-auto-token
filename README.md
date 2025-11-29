# AB Bricks Auto Token

Automatically generates Bricks Builder Dynamic Tokens and Conditions based on ACF/MetaBox field naming conventions.

## Description

AB Bricks Auto Token eliminates manual token creation for Bricks Builder by automatically scanning your ACF and MetaBox field groups and registering them as Dynamic Data Tokens and Conditions. This ensures dynamic data is available consistently across templates, loops, and components.

## Features

- Automatically registers Bricks Dynamic Data Tokens from ACF/MetaBox fields
- Generates Bricks Conditions based on boolean/status-type fields
- Supports all MetaBox + ACF field types that output simple values
- Namespaced tokens to avoid conflicts
- Integrates seamlessly with Bricks "Dynamic Data" dropdowns
- Full WordPress multisite support
- No admin UI required - works automatically

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Bricks Builder theme
- At least one of:
  - Advanced Custom Fields (ACF)
  - MetaBox

## Installation

1. Download the plugin
2. Upload to `/wp-content/plugins/ab-bricks-auto-token/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically start registering tokens based on your existing ACF/MetaBox fields

## Quick Start

### Naming Pattern
```
field_name__post_type__token|condition
```

### Example
**ACF Field Name:** `author_bio__post__token`

**Creates Token:** `{post_author_bio}`

The token is now available in Bricks Builder's Dynamic Data dropdown under "Post (auto)".

### Full Usage Guide

See [USAGE.md](USAGE.md) for:
- Detailed examples
- ACF and MetaBox field creation
- Using tokens and conditions in Bricks
- Advanced integration development
- Troubleshooting

## How It Works

Once activated, the plugin automatically:

1. Scans your ACF and MetaBox field groups
2. Identifies fields following the naming pattern
3. Registers them as Bricks Dynamic Tokens
4. Creates Bricks Conditions for conditional display

Tokens appear in Bricks Builder's Dynamic Data dropdown menus organized by post type.

## Documentation

- **[USAGE.md](USAGE.md)** - User guide with examples and troubleshooting
- **[BRICKS_AUTO.md](BRICKS_AUTO.md)** - Technical implementation details and API reference
- **[CLAUDE.md](CLAUDE.md)** - Code style guidelines and development standards

## Developer Notes

### Code Structure

- `src/` - Core plugin classes (PSR-4 autoloaded)
  - `src/Contracts/` - Interfaces for integrations
  - `src/Abstracts/` - Base classes for integrations
  - `src/Integrations/ACF/` - ACF integration module
  - `src/Integrations/MetaBox/` - MetaBox integration module
  - `src/IntegrationRegistry.php` - Module registration system
- `assets/` - CSS and JavaScript files
- `languages/` - Translation files

### Extending with Custom Integrations

External plugins can register custom integrations:

```php
add_action('ab_bricks_auto_token_register_integrations', function() {
    AB\BricksAutoToken\IntegrationRegistry::register(
        YourCustomIntegration::class
    );
});
```

See [USAGE.md](USAGE.md) for detailed integration development guide.

## License

GPL v2 or later

## Support

For issues and feature requests, please visit:
https://github.com/wpeasy/ab-bricks-auto-token/issues
