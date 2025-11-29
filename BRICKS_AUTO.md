# Bricks Builder Auto-Discovery System

## Overview

This document explains the auto-discovery system that automatically generates Bricks Builder dynamic data tags (tokens) and conditionals based on ACF and MetaBox field naming conventions. This allows you to create custom Bricks tokens and conditionals without writing PHP code for each one.

## Purpose

The auto-discovery system:
- Automatically creates Bricks dynamic data tags from ACF and MetaBox fields
- Automatically creates Bricks conditionals from ACF and MetaBox fields
- Eliminates the need to hardcode individual tokens and conditionals
- Provides a consistent naming pattern for developers
- Dynamically adapts as you add/modify ACF or MetaBox fields

## Requirements

### WordPress Plugins
- Bricks Builder
- At least one of:
  - Advanced Custom Fields (ACF) or ACF Pro
  - MetaBox
- PHP 7.4+ (8.0+ recommended)

### WordPress Hooks Used
- `bricks/dynamic_tags_list` - Register dynamic data tags
- `bricks/dynamic_data/render_tag` - Render individual tags
- `bricks/dynamic_data/render_content` - Render tags in content
- `bricks/frontend/render_data` - Frontend tag rendering
- `bricks/conditions/groups` - Register conditional groups
- `bricks/conditions/options` - Register conditional options
- `bricks/conditions/result` - Evaluate conditionals

## Critical Implementation Details

### Plugin Initialization Timing

**IMPORTANT:** Bricks is a **theme**, not a plugin. This means `BRICKS_VERSION` is not defined during the `plugins_loaded` hook.

**Solution:** Initialize your plugin on the `after_setup_theme` hook instead of `plugins_loaded`:

```php
// WRONG - Bricks not loaded yet
add_action('plugins_loaded', function() {
    if (defined('BRICKS_VERSION')) {
        // This will never be true!
    }
});

// CORRECT - Theme loads before this hook
add_action('after_setup_theme', function() {
    if (defined('BRICKS_VERSION')) {
        // This works!
        YourPlugin::init();
    }
}, 100); // Priority 100 ensures theme fully loaded
```

### Conditions Data Format

**CRITICAL:** The conditions hooks expect a **specific array format**. Getting this wrong means conditions won't appear in Bricks editor.

#### Conditions Groups Format

**WRONG:**
```php
public static function register_conditionals_group(array $groups): array {
    $groups['my_group_key'] = 'My Group Label';
    return $groups;
}
```

**CORRECT:**
```php
public static function register_conditionals_group(array $groups): array {
    $groups[] = [
        'name' => 'my_group_key',
        'label' => 'My Group Label',
    ];
    return $groups;
}
```

**Key Difference:**
- Groups must be an **indexed array** with `name` and `label` keys
- Do NOT use associative array with key => value format

#### Conditions Options Format

**CRITICAL:** The `compare` key must have a specific nested structure with `type`, `options`, and `placeholder`:

**WRONG:**
```php
public static function register_conditionals(array $options): array {
    $options[] = [
        'key' => 'my_condition_key',
        'label' => 'My Condition',
        'group' => 'my_group_key',
        'compare' => [
            '==' => 'equals',         // This won't work!
            '!=' => 'not equals',
        ],
    ];
    return $options;
}
```

**CORRECT:**
```php
public static function register_conditionals(array $options): array {
    $options[] = [
        'key' => 'my_condition_key',      // Required: unique identifier
        'label' => 'My Condition',        // Required: display label
        'group' => 'my_group_key',        // Required: must match group 'name'
        'compare' => [                     // Required: comparison operators
            'type' => 'select',            // Required: must be 'select'
            'options' => [                 // Required: the actual operators
                '==' => 'equals',
                '!=' => 'not equals',
                'contains' => 'contains',
                'empty' => 'is empty',
                'not_empty' => 'is not empty',
            ],
            'placeholder' => 'equals',     // Required: default value
        ],
    ];
    return $options;
}
```

**For Boolean Fields:**
```php
'compare' => [
    'type' => 'select',
    'options' => [
        '==' => 'is true',
        '!=' => 'is false',
    ],
    'placeholder' => 'is true',
]
```

### Filter Hook Priority

Tokens and conditions work fine with default priority. Early priority (1) is not required but doesn't hurt:

```php
// These both work
add_filter('bricks/conditions/groups', [self::class, 'register_conditionals_group']);
add_filter('bricks/conditions/groups', [self::class, 'register_conditionals_group'], 1);
```

## Naming Convention

The system uses a double-underscore (`__`) pattern to identify fields:

```
field_name__post_type__token
field_name__post_type__condition
field_name__post_type__token__condition
```

### Pattern Components

| Component | Description | Examples |
|-----------|-------------|----------|
| `field_name` | The actual meta field name used to store the value | `program_name`, `company_url`, `is_featured` |
| `post_type` | The post type slug this field belongs to | `brand`, `event`, `sponsor`, `company-page` |
| `__token` | Creates a Bricks dynamic data tag | Creates `{brand_program_name}` |
| `__condition` | Creates a Bricks conditional | Creates "Program Name" conditional |

### Examples

**Example 1: Token Only**
```
ACF Field Name: program_name__brand__token
MetaBox Field ID: program_name__brand__token

Creates:
  Token: {brand_program_name}
  Group: "Brand (auto)"
  Outputs: Value from current brand post's program_name__brand__token field
```

**Example 2: Condition Only**
```
ACF Field Name: is_featured__event__condition
MetaBox Field ID: is_featured__event__condition

Creates:
  Conditional: "Is Featured"
  Group: "Event (auto)"
  Operators: equals, not equals, contains, is empty, is not empty
```

**Example 3: Both Token and Condition**
```
ACF Field Name: company_url__brand__token__condition
MetaBox Field ID: company_url__brand__token__condition

Creates:
  Token: {brand_company_url}
  Group: "Brand (auto)"
  Conditional: "Company Url"
  Group: "Brand (auto)"
```

## How It Works

### 1. Field Discovery

The system scans all field groups from both ACF and MetaBox:

#### ACF Field Discovery
1. Gets all field groups using `acf_get_field_groups()`
2. For each group, gets all fields using `acf_get_fields($group['key'])`
3. Parses each field name for the pattern `field_name__post_type__token|condition`
4. Extracts post types from both:
   - The field name pattern
   - The field group's location rules (for multi-post-type support)

#### MetaBox Field Discovery
1. Gets all meta boxes using `rwmb_get_registry('meta_box')->all()`
2. For each meta box, gets all fields from the configuration
3. Parses each field ID for the pattern `field_name__post_type__token|condition`
4. Extracts post types from both:
   - The field ID pattern
   - The meta box's `post_types` configuration array

### 2. Token Generation

For fields with `__token` in the name:
1. Token name is generated as: `{post_type}__{field_name}`
   - Example: `program_name__brand__token` becomes `{brand_program_name}`
2. Label is auto-generated from field name: `ucwords(str_replace('_', ' ', $field_name))`
   - Example: `program_name` becomes "Program Name"
3. Group name is formatted as: `{Post Type Label} (auto)`
   - Example: "Brand (auto)"
4. Registered via `bricks/dynamic_tags_list` filter

### 3. Token Rendering

When Bricks encounters a token like `{brand_program_name}`:
1. `render_tag()` is called via `bricks/dynamic_data/render_tag` filter
2. System checks if token matches any auto-discovered fields
3. Gets the current post ID from context
4. Retrieves value using the **full field name/ID** (e.g., `program_name__brand__token`)
   - **ACF fields:** Uses `get_field()` with the full field name
   - **MetaBox fields:** Uses `rwmb_get_value()` with the full field ID
   - Falls back to `get_post_meta()` if neither is available
5. Returns the field value

**Important:** The system uses the full field name/ID (with `__token`) to retrieve values from the database, not just the base field name.

### 4. Conditional Generation

For fields with `__condition` in the name:
1. Conditional key is generated as: `wfh_auto_{post_type}_{field_name}`
   - Example: `wfh_auto_brand_program_name`
2. Label is auto-generated from field name
3. Group key is: `wfh_auto_{post_type}`
4. Compare options are determined by field type:
   - **ACF Boolean fields (`true_false`)**: `is true`, `is false`
   - **MetaBox Checkbox fields (`checkbox`)**: `is true`, `is false`
   - **All other field types**: `equals`, `not equals`, `contains`, `is empty`, `is not empty`

### 5. Conditional Evaluation

When Bricks evaluates a conditional:
1. `evaluate_conditional()` is called via `bricks/conditions/result` filter
2. System checks if conditional key matches any auto-discovered fields
3. Gets the current post ID
4. Retrieves value using the **full field name/ID** (e.g., `program_name__brand__condition`)
   - **ACF fields:** Uses `get_field()` with the full field name
   - **MetaBox fields:** Uses `rwmb_get_value()` with the full field ID
   - Falls back to `get_post_meta()` if neither is available
5. Compares value based on operator:
   - `==` - Loose equality
   - `!=` - Loose inequality
   - `contains` - String contains check
   - `empty` - Empty check
   - `not_empty` - Not empty check

### 6. Multiple Post Types

If a field group/meta box is assigned to multiple post types:
- The system creates tokens/conditionals for **each** post type
- **ACF Example:**
  ```
  ACF Field Name: sponsor_name__sponsor__token
  Field Group Location: post_type == sponsor AND post_type == brand

  Creates:
    {sponsor_sponsor_name} - for sponsor post type
    {brand_sponsor_name} - for brand post type
  ```
- **MetaBox Example:**
  ```
  MetaBox Field ID: sponsor_name__sponsor__token
  Meta Box post_types: ['sponsor', 'brand']

  Creates:
    {sponsor_sponsor_name} - for sponsor post type
    {brand_sponsor_name} - for brand post type
  ```

## Implementation Guide

### File Structure

Create a class file (e.g., `BricksIntegration.php`) with these methods:

```php
namespace YourPlugin\Integration;

final class BricksIntegration {
    // Cache for performance
    private static ?array $auto_fields_cache = null;

    // Constants for group names
    private const CONDITIONALS_GROUP = 'your_prefix';
    private const BRAND_GROUP = 'Your Plugin';

    public static function init(): void;
    public static function register_dynamic_tags(array $tags): array;
    public static function render_tag($tag, $post, string $context = 'text');
    public static function render_content(string $content, $post, string $context = 'text'): string;
    public static function register_conditionals_group(array $groups): array;
    public static function register_conditionals(array $options): array;
    public static function evaluate_conditional(bool $result, string $key, array $condition): bool;

    // Auto-discovery methods
    private static function get_auto_discovered_fields(): array;
    private static function parse_field_pattern(string $field_name): ?array;
    private static function get_field_group_post_types(array $field_group): array;
    private static function format_post_type_label(string $post_type): string;
    private static function build_compare_options(string $field_type): array;
    private static function render_auto_token(string $token_name, $post): ?string;
    private static function evaluate_auto_conditional(string $key, array $condition): ?bool;
}
```

### Core Methods Explained

#### `get_auto_discovered_fields()`

This is the heart of the system. It:
1. Returns cached results if available (performance optimization)
2. Discovers fields from both ACF and MetaBox:

   **ACF Discovery:**
   - Gets all ACF field groups via `acf_get_field_groups()`
   - For each group, gets all fields via `acf_get_fields($group['key'])`
   - Gets post types from location rules
   - Parses each field name for the pattern

   **MetaBox Discovery:**
   - Gets all meta boxes via `rwmb_get_registry('meta_box')->all()`
   - For each meta box, gets all fields from configuration
   - Gets post types from the `post_types` array
   - Parses each field ID for the pattern

3. Creates entry for each discovered token/conditional
4. Builds a structured array with all metadata needed
5. Caches the results for subsequent calls

**Returns:** Array of discovered fields with structure:
```php
[
    'meta_name' => 'program_name',
    'full_field_name' => 'program_name__brand__token',
    'post_type' => 'brand',
    'type' => 'token', // or 'condition'
    'acf_field' => [...], // Full ACF field data
    'acf_field_type' => 'text',
    'token_name' => 'brand_program_name',
    'conditional_key' => 'wfh_auto_brand_program_name',
    'label' => 'Program Name',
    'group' => 'Brand (auto)',
    'group_key' => 'wfh_auto_brand',
    'compare_options' => [...] // Only for conditionals
]
```

#### `parse_field_pattern()` - Line 752-797

Parses field names to extract:
- Base field name
- Post type
- Type indicators (token and/or condition)

**Pattern:** `field_name__post_type__token|condition`

Returns array of parsed results (can have multiple if both `__token` and `__condition` present).

#### `render_auto_token()` - Line 878-915

Called during token rendering to:
1. Match token name to discovered fields
2. Get current post ID
3. Use **full ACF field name** to retrieve value
4. Return value as string

**Critical:** Always use `$field['full_field_name']` to get the value, not just the meta name.

#### `evaluate_auto_conditional()` - Line 918-973

Called during conditional evaluation to:
1. Match conditional key to discovered fields
2. Get current post ID
3. Use **full ACF field name** to retrieve value
4. Compare value based on operator
5. Return boolean result

#### `build_compare_options()` - Line 845-875

Creates appropriate comparison operators based on ACF field type:
- Boolean fields: simplified to `is true` / `is false`
- All others: full set of operators

### Hook Registration

In your `init()` method:

```php
public static function init(): void {
    // Register dynamic data tags
    add_filter('bricks/dynamic_tags_list', [self::class, 'register_dynamic_tags']);

    // Render single tags
    add_filter('bricks/dynamic_data/render_tag', [self::class, 'render_tag'], 20, 3);

    // Render tags in content
    add_filter('bricks/dynamic_data/render_content', [self::class, 'render_content'], 20, 3);
    add_filter('bricks/frontend/render_data', [self::class, 'render_content'], 20, 2);

    // Register conditionals
    add_filter('bricks/conditions/groups', [self::class, 'register_conditionals_group']);
    add_filter('bricks/conditions/options', [self::class, 'register_conditionals']);
    add_filter('bricks/conditions/result', [self::class, 'evaluate_conditional'], 10, 3);
}
```

### Token Registration Example

```php
public static function register_dynamic_tags(array $tags): array {
    // Get auto-discovered fields
    $auto_fields = self::get_auto_discovered_fields();

    foreach ($auto_fields as $field) {
        if ($field['type'] === 'token') {
            $tags[] = [
                'name' => '{' . $field['token_name'] . '}',
                'label' => $field['label'],
                'group' => $field['group'],
            ];
        }
    }

    return $tags;
}
```

### Conditional Registration Example

```php
public static function register_conditionals(array $options): array {
    // Get auto-discovered fields
    $auto_fields = self::get_auto_discovered_fields();

    foreach ($auto_fields as $field) {
        if ($field['type'] === 'condition') {
            $options[] = [
                'key' => $field['conditional_key'],
                'label' => $field['label'],
                'group' => $field['group_key'],
                'compare' => $field['compare_options'],
            ];
        }
    }

    return $options;
}
```

## Key Implementation Details

### 1. Caching

Always cache the discovered fields to avoid repeated ACF queries:
```php
private static ?array $auto_fields_cache = null;

private static function get_auto_discovered_fields(): array {
    if (self::$auto_fields_cache !== null) {
        return self::$auto_fields_cache;
    }

    // ... discovery logic ...

    self::$auto_fields_cache = $discovered;
    return self::$auto_fields_cache;
}
```

### 2. Field Name Storage

**Critical:** Both ACF and MetaBox store field values using the full field name/ID as the meta key. When retrieving values, always use the full field name/ID:

**ACF Example:**
```php
// CORRECT - uses full field name
$value = get_field('program_name__brand__token', $post_id);

// INCORRECT - won't find the value
$value = get_field('program_name', $post_id);
```

**MetaBox Example:**
```php
// CORRECT - uses full field ID
$value = rwmb_get_value('program_name__brand__token', [], $post_id);

// INCORRECT - won't find the value
$value = rwmb_get_value('program_name', [], $post_id);
```

### 3. Pattern Parsing

The pattern supports multiple indicators in one field name:
- `field__post_type__token` - Creates only a token
- `field__post_type__condition` - Creates only a conditional
- `field__post_type__token__condition` - Creates both

The parser returns an array to handle multiple indicators.

### 4. Post Type Detection

Post types are gathered from two sources:
1. **Field name pattern** - The `post_type` component in the name
2. **Location rules** - ACF field group location rules (`post_type == brand`)

This allows flexibility in how you assign fields to post types.

### 5. Nested Token Support

The system can handle nested tokens (though not auto-discovered):
```php
{brand_program_status:{users_brand_id}}
```

The inner token is resolved first, then used as a parameter.

### 6. Context Awareness

Tokens can behave differently based on context:
- `text` context: Return string/HTML
- `image` context: Return array of image IDs

Example from brand logo:
```php
if ($context === 'image') {
    return ($resolved['id'] ? [(int) $resolved['id']] : []);
}
return $resolved['html'] ?? '';
```

### 7. Content Rendering

The `render_content()` method handles tags within larger content blocks and supports nested braces by tracking brace depth.

## Testing the Implementation

### 1. Create a Test Field

**For ACF:**
In ACF, create a field group with:
- **Field Name:** `test_value__brand__token__condition`
- **Field Type:** Text
- **Location:** Post Type is equal to Brand

**For MetaBox:**
Register a meta box with:
```php
[
    'id' => 'test_meta_box',
    'title' => 'Test Fields',
    'post_types' => ['brand'],
    'fields' => [
        [
            'id' => 'test_value__brand__token__condition',
            'type' => 'text',
            'name' => 'Test Value',
        ],
    ],
]
```

### 2. Test Token

In Bricks:
1. Edit a brand post
2. Add a Text element
3. Use dynamic data: `{brand_test_value}`
4. Should output the field value

### 3. Test Conditional

In Bricks:
1. Edit a template
2. Add a Section
3. Add Condition: "Test Value" (from "Brand (auto)" group)
4. Set to "is not empty"
5. Section should only show if field has a value

## Troubleshooting

### Tokens Not Appearing

1. Check field name/ID follows pattern exactly
2. Verify ACF or MetaBox is active and field group/meta box is registered
3. Clear any caching (object cache, page cache)
4. Check if either `acf_get_field_groups()` or `rwmb_get_registry()` function exists
5. For MetaBox, ensure the meta box is properly registered via `rwmb_meta_boxes` filter

### Values Not Displaying

1. Verify you're using the **full field name/ID** to retrieve values
   - **ACF:** Use `get_field('full_field_name__post_type__token', $post_id)`
   - **MetaBox:** Use `rwmb_get_value('full_field_id__post_type__token', [], $post_id)`
2. Check the post has the field value saved
3. Confirm you're on the correct post type
4. Check field storage format (meta vs options)
5. For MetaBox, ensure proper args array is passed to `rwmb_get_value()`

### Conditionals Not Working

1. Verify conditional key matches auto-generated format: `wfh_auto_{post_type}_{field_name}`
2. Check comparison logic matches field type
3. Ensure field value exists and is correct type
4. Test with simple `is not empty` first

### Multiple Post Types Not Working

1. Check field group location rules include all desired post types
2. Verify `get_field_group_post_types()` extracts them correctly
3. Check for duplicate tokens with same name (should be unique per post type)

## Performance Considerations

### Caching

- Results are cached in a static variable for the request lifetime
- No persistent caching (object cache) to ensure fresh results after ACF changes
- Consider adding transient caching if you have many field groups

### Hook Priority

- Use priority `20` on render hooks to allow other filters to run first
- Use priority `10` on conditional evaluation for standard precedence

### Query Optimization

- Discovery only runs once per request due to caching
- ACF already has internal caching for field groups
- Consider lazy loading if you have 100+ field groups

## Advanced Features

### Custom Token Logic

You can still add manual tokens alongside auto-discovered ones:

```php
public static function register_dynamic_tags(array $tags): array {
    // Manual tokens
    $tags[] = [
        'name' => '{custom_token}',
        'label' => 'Custom Token',
        'group' => 'Custom Group',
    ];

    // Auto-discovered tokens
    $auto_fields = self::get_auto_discovered_fields();
    foreach ($auto_fields as $field) {
        if ($field['type'] === 'token') {
            $tags[] = [
                'name' => '{' . $field['token_name'] . '}',
                'label' => $field['label'],
                'group' => $field['group'],
            ];
        }
    }

    return $tags;
}
```

### Custom Conditionals

Same approach for conditionals - mix manual and auto-discovered.

### Field Type Specific Handling

You can add custom logic based on ACF field type:

```php
private static function render_auto_token(string $token_name, $post): ?string {
    $auto_fields = self::get_auto_discovered_fields();

    foreach ($auto_fields as $field) {
        if ($field['type'] === 'token' && $field['token_name'] === $token_name) {
            $value = get_field($field['full_field_name'], $post_id);

            // Handle different field types
            if ($field['acf_field_type'] === 'image') {
                return wp_get_attachment_image($value, 'large');
            }

            if ($field['acf_field_type'] === 'true_false') {
                return $value ? 'Yes' : 'No';
            }

            // Default: return as string
            return (string) $value;
        }
    }

    return null;
}
```

## Migration from Hardcoded System

If you're migrating from hardcoded tokens:

### Before (Hardcoded)
```php
$tags[] = [
    'name' => '{brand_program_name}',
    'label' => 'Program Name',
    'group' => 'Brand',
];

// In render method
if ($tag === 'brand_program_name') {
    return get_field('program_name', $post_id);  // ACF
    // OR
    return rwmb_get_value('program_name', [], $post_id);  // MetaBox
}
```

### After (Auto-Discovery)

**For ACF:**
```
ACF Field Name: program_name__brand__token
```

**For MetaBox:**
```php
[
    'id' => 'program_name__brand__token',
    'type' => 'text',
    'name' => 'Program Name',
]
```

That's it! The token is automatically created and rendered.

## Summary

The auto-discovery system provides:
- **Automatic token creation** - No PHP code needed for each token
- **Automatic conditional creation** - Conditionals generated from same fields
- **Dual plugin support** - Works with both ACF and MetaBox
- **Consistent naming** - Pattern-based approach ensures consistency
- **Multi-post-type support** - One field can create tokens for multiple CPTs
- **Performance** - Cached results, minimal database queries
- **Flexibility** - Mix auto-discovered and manual tokens/conditionals

By following the naming pattern `field_name__post_type__token|condition`, you can rapidly create Bricks dynamic data tags and conditionals for both ACF and MetaBox fields without touching PHP code for each addition.

## Reference Implementation

For a complete working implementation, see the plugin source code:

**ACF Integration:**
- `src/Integrations/ACF/` - ACF-specific implementation
- Field discovery via `acf_get_field_groups()` and `acf_get_fields()`
- Value retrieval via `get_field()`

**MetaBox Integration:**
- `src/Integrations/MetaBox/` - MetaBox-specific implementation
- Field discovery via `rwmb_get_registry('meta_box')->all()`
- Value retrieval via `rwmb_get_value()`

**Core Methods:**
- `get_auto_discovered_fields()` - Core discovery logic for both plugins
- `parse_field_pattern()` - Pattern parsing
- `render_auto_token()` - Token rendering
- `evaluate_auto_conditional()` - Conditional evaluation
