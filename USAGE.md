# AB Bricks Auto Token - Usage Guide

## Quick Start

### 1. Installation

1. Upload the plugin to `/wp-content/plugins/ab-bricks-auto-token/`
2. Activate the plugin
3. Ensure you have:
   - Bricks Builder installed and activated
   - At least one of: ACF or MetaBox

### 2. Create Your First Auto Token

**Using ACF:**
1. Create a new ACF Field Group
2. Add a field with this naming pattern: `field_name__post_type__token`
   - Example: `author_bio__post__token`
3. Assign the field group to a post type
4. The token `{post_author_bio}` is now available in Bricks!

**Using MetaBox:**
1. Register a meta box with fields following the pattern
2. Example:
```php
add_filter('rwmb_meta_boxes', function($meta_boxes) {
    $meta_boxes[] = [
        'id' => 'product_info',
        'title' => 'Product Information',
        'post_types' => ['product'],
        'fields' => [
            [
                'id' => 'manufacturer__product__token',
                'type' => 'text',
                'name' => 'Manufacturer',
            ],
        ],
    ];
    return $meta_boxes;
});
```
3. The token `{product_manufacturer}` is now available in Bricks!

## Naming Pattern

### Pattern Structure
```
field_name__post_type__token|condition
```

### Components

| Component | Required | Description |
|-----------|----------|-------------|
| `field_name` | Yes | The actual meta field name (e.g., `author_bio`) |
| `post_type` | Yes | Post type slug (e.g., `post`, `product`, `event`) |
| `__token` | Optional | Creates a Bricks dynamic token |
| `__condition` | Optional | Creates a Bricks condition |

### Examples

**Token Only:**
```
author_bio__post__token
```
Creates: `{post_author_bio}`

**Condition Only:**
```
is_featured__product__condition
```
Creates: "Is Featured" condition in "Product (auto)" group

**Both Token and Condition:**
```
stock_status__product__token__condition
```
Creates:
- Token: `{product_stock_status}`
- Condition: "Stock Status"

## Using Tokens in Bricks

### In Elements

1. Add any Bricks element (Text, Heading, etc.)
2. Click the dynamic data icon
3. Find your token under "[Post Type] (auto)" group
4. Select your auto-generated token

### In Content

You can also type tokens directly:
```
Product by {product_manufacturer}
```

## Using Conditions in Bricks

### Adding Conditions

1. Select any Bricks element
2. Go to Conditions tab
3. Click "Add Condition"
4. Find your condition under "[Post Type] (auto)" group
5. Choose comparison operator
6. Set value (if needed)

### Available Operators

**For Boolean Fields:**
- is true
- is false

**For All Other Fields:**
- equals
- not equals
- contains
- is empty
- is not empty

## Field Type Support

### ACF Field Types

All ACF field types are supported:
- Text, Textarea, Number
- True/False (gets simplified boolean operators)
- Select, Radio, Checkbox
- Relationship, Post Object
- URL, Email
- And more...

### MetaBox Field Types

All MetaBox field types are supported:
- text, textarea, number
- checkbox (gets simplified boolean operators)
- select, radio
- post, taxonomy
- url, email
- And more...

## Multi Post Type Support

If you assign a field group/meta box to multiple post types, tokens are created for each:

**ACF Example:**
```
Field Name: sponsor_name__sponsor__token
Locations: Post Type == sponsor AND Post Type == brand

Creates:
  {sponsor_sponsor_name}
  {brand_sponsor_name}
```

**MetaBox Example:**
```php
'post_types' => ['sponsor', 'brand'],
'fields' => [
    ['id' => 'sponsor_name__sponsor__token', ...]
]

Creates:
  {sponsor_sponsor_name}
  {brand_sponsor_name}
```

## Advanced Usage

### External Integration Registration

Developers can register custom integrations using the action hook:

```php
add_action('ab_bricks_auto_token_register_integrations', function() {
    // Register your custom integration
    AB\BricksAutoToken\IntegrationRegistry::register(
        YourCustomIntegration::class
    );
});
```

### Creating a Custom Integration

1. Create a class implementing `AB\BricksAutoToken\Contracts\IntegrationInterface`
2. Or extend `AB\BricksAutoToken\Abstracts\BaseIntegration`
3. Implement required methods:
   - `get_name()` - Return integration name
   - `is_available()` - Check if integration should load
   - `init()` - Initialize hooks
   - `get_discovered_fields()` - Return discovered fields array
   - `get_field_value()` - Retrieve field value

Example:
```php
namespace YourPlugin\Integrations;

use AB\BricksAutoToken\Abstracts\BaseIntegration;

final class CustomFieldsIntegration extends BaseIntegration {

    public static function get_name(): string {
        return 'custom_fields';
    }

    public static function is_available(): bool {
        return class_exists('YourCustomFieldsPlugin');
    }

    public static function init(): void {
        // Register Bricks hooks
        add_filter('bricks/dynamic_tags_list', [self::class, 'register_dynamic_tags']);
        // ... other hooks
    }

    public static function get_discovered_fields(): array {
        // Your field discovery logic
    }

    public static function get_field_value(string $field_name, int $post_id) {
        // Your value retrieval logic
    }
}
```

## Troubleshooting

### Tokens Not Appearing

1. **Check field naming:** Ensure pattern is exact: `name__type__token`
2. **Verify plugins:** ACF or MetaBox must be active
3. **Check Bricks:** Bricks Builder must be active
4. **Clear cache:** Clear WordPress object cache and page cache
5. **Check post type:** Field must be assigned to a post type

### Values Not Displaying

1. **Check field has value:** Ensure the post has data saved
2. **Verify post type:** Token will only work on assigned post types
3. **Check field name:** System uses FULL field name (including `__token`)

### Conditionals Not Working

1. **Check operator:** Ensure you're using the right comparison
2. **Test with "is not empty":** Start simple to verify field is found
3. **Check value format:** Some fields return arrays or objects
4. **Verify post context:** Conditions need valid post context

## Performance

### Caching

The plugin caches discovered fields for each request:
- Fields are discovered once per page load
- Cache is stored in static variables
- No persistent cache to ensure fresh results after field changes

### Best Practices

1. **Use specific patterns:** Don't create hundreds of auto-tokens
2. **Leverage groups:** Use post types to organize tokens logically
3. **Monitor performance:** If you have 50+ field groups, consider manual tokens for frequently used fields

## Examples

### Blog Author Box
```php
// ACF Fields
author_name__post__token
author_bio__post__token__condition
author_photo__post__token

// Usage in Bricks
{post_author_name}
{post_author_bio}
{post_author_photo}

// Condition: Only show if author_bio is not empty
```

### Product Catalog
```php
// MetaBox Fields
price__product__token
manufacturer__product__token__condition
in_stock__product__condition

// Usage in Bricks
Price: {product_price}
By: {product_manufacturer}

// Conditions:
// - Show "In Stock" badge if in_stock is true
// - Show manufacturer info if manufacturer is not empty
```

### Event Listings
```php
// ACF Fields
event_date__event__token
event_location__event__token
is_virtual__event__condition
registration_url__event__token__condition

// Usage in Bricks
{event_date} at {event_location}
Register: {event_registration_url}

// Conditions:
// - Show virtual badge if is_virtual is true
// - Show registration button if registration_url is not empty
```

## Support

For issues, feature requests, or contributions:
- GitHub: https://github.com/wpeasy/ab-bricks-auto-token
- Documentation: See BRICKS_AUTO.md for technical implementation details
