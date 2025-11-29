# AB Bricks Auto Token – CLAUDE.md

**Plugin URI**: https://github.com/wpeasy/ab-bricks-auto-token
**Requires at least**: 6.0
**Tested Version**: {latest wordpress}
**Min PHP**: 7.4
**Tested PHP**: 8.3

## Purpose
AB Bricks Auto Token automatically generates Bricks Builder Dynamic Tokens and Bricks Conditions based on the naming conventions of Meta Fields created using **MetaBox** or **Advanced Custom Fields (ACF)**.  
This eliminates manual token creation and ensures dynamic data is available consistently across templates, loops, and components.

## Functionality

### Core Features
- Scans ACF and MetaBox field groups for fields following specific naming rules.
- Automatically registers Bricks Dynamic Data Tokens.
- Automatically generates Bricks Conditions based on boolean / status-type fields.
- Supports all MetaBox + ACF field types that output simple values (text, number, boolean, selects, relationships, URLs, etc.).
- Ensures tokens are namespaced to avoid conflicts.
- Integrates with Bricks “Dynamic Data” dropdowns.
- Registers conditions under a dedicated group (e.g., “AB Auto Token Conditions”).
- Full support for WordPress multisite.
- No admin UI required.

## Code Style Guidelines

### General
1. All libraries must be downloaded and served locally (no CDN use).

### PHP Conventions
1. **Namespace:** All plugin classes use `AB\BricksAutoToken`.
2. **Loading:** Use **Composer** with PSR-4 autoloading.
3. **Class Structure:**  
   - Classes should be `final`.  
   - Use **static methods** for WordPress hooks (`init()`, etc.).
4. **Security:**  
   - All PHP files begin with:  
     `defined('ABSPATH') || exit;`
5. **Sanitization:**  
   - Always use WordPress sanitization functions for all user input.
6. **Nonces:**  
   - Use WP nonces for all actions.  
   - Use a custom nonce system for REST API endpoints.
7. **Constants:**  
   - Define plugin paths/URLs using:  
     - `AB_BRICKS_AUTO_TOKEN_PLUGIN_PATH`  
     - `AB_BRICKS_AUTO_TOKEN_PLUGIN_URL`

### Method Patterns
- `init()` — Registers WordPress hooks and Bricks filters.
- `register_tokens()` — Creates Bricks dynamic tokens.
- `register_conditions()` — Creates Bricks conditions.
- `render()` — Any output functions.
- Handlers named `handle_*()`.
- Private helpers may be prefixed with `_`.
- Extensive type checking + strict parameter validation.

### JavaScript Conventions
1. No external CDNs; all libraries must be local.
2. Use **AlpineJS where appropriate**, initialized via `"init"` event.
3. Use **Svelte 5** for any UI that may be added in future versions.
4. Use native **ES6 modules** — never jQuery.
5. Admin interface guidelines (even though none exists now):  
   - Tab switching with JS/CSS (no reloads).  
   - Auto-save on change, no “Save” button.  
   - Status indicator to show “saved”/“saving”.

### CSS
1. **Frontend:** Use `@layer`.  
2. **Admin:** *Never* use `@layer`.  
3. Prefer nested CSS.
4. Prefer **Container Queries** over media queries where possible.

### Security Practices
- Same-origin enforcement for REST API.
- Nonce validation on all endpoints.
- Full sanitization on all field values and token outputs.

### WordPress Integration
- Follow WP coding standards.
- Use WordPress APIs extensively (Settings API, REST, CPTs, Filters).
- Fully translation-ready (text domain derived from plugin slug).
- Uses WP Media Library if needed for future versions.
- Fully compatible with WordPress Multisite.

### Development Features
- Composer (PSR-4) autoloading.
- Graceful fallbacks (Alpine optional).
- Extensive error handling & validation.
- Use a Modular structure for integrations e.g. integrations/MetaBox

## Version 1.0.0 Release Notes

### Architecture Decisions
- **Hook Timing**: Plugin initializes on `after_setup_theme` with priority 100 (not `plugins_loaded`) because Bricks is a theme and `BRICKS_VERSION` isn't available during `plugins_loaded`
- **Integration Registry**: Modular system allows external plugins to register custom integrations via `ab_bricks_auto_token_register_integrations` hook
- **Cache Strategy**: Field discovery cache currently disabled to ensure immediate field discovery. Static cache only persists for single request duration, not between requests. Future versions will implement proper cache invalidation hooks.

### Critical Bricks Integration Details
1. **Conditions Groups Format**: Must use indexed array with `name` and `label` keys:
   ```php
   $groups[] = ['name' => $key, 'label' => $label];
   ```
   Not: `$groups[$key] = $label;`

2. **Conditions Compare Options Format**: Must use nested structure:
   ```php
   'compare' => [
       'type' => 'select',
       'options' => ['==' => 'equals'],
       'placeholder' => 'equals'
   ]
   ```
   Not: `'compare' => ['==' => 'equals']`

3. **Field Naming Pattern**: `field_name__post_type__token__condition`
   - `field_name`: Meta field name (lowercase, underscores)
   - `post_type`: Post type slug
   - `__token`: Creates dynamic token `{post_type_field_name}`
   - `__condition`: Creates Bricks condition in group `ab_auto_[post_type]`

### Admin Interface
- Instructions page accessible via top-level menu and plugin action links
- Two-tab interface: Basic Usage and Developer Guide
- Includes troubleshooting tips and cache behavior documentation
- No settings page required - everything works via field naming conventions

