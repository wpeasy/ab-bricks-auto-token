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

## Usage

Once activated, the plugin automatically:

1. Scans your ACF and MetaBox field groups
2. Identifies fields following naming conventions
3. Registers them as Bricks Dynamic Tokens
4. Creates conditions for boolean/status fields

Tokens will appear in Bricks Builder's Dynamic Data dropdown menus.

## Developer Notes

See [CLAUDE.md](CLAUDE.md) for detailed code style guidelines and development standards.

### Code Structure

- `includes/` - Core plugin classes
- `integrations/` - Integration modules for ACF and MetaBox
- `assets/` - CSS and JavaScript files
- `languages/` - Translation files

## License

GPL v2 or later

## Support

For issues and feature requests, please visit:
https://github.com/wpeasy/ab-bricks-auto-token/issues
