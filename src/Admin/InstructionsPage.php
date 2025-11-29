<?php
/**
 * Admin Instructions Page
 *
 * @package AB\BricksAutoToken\Admin
 */

namespace AB\BricksAutoToken\Admin;

defined('ABSPATH') || exit;

/**
 * Handles the admin instructions page
 */
final class InstructionsPage {
    /**
     * Initialize the admin page
     *
     * @return void
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu_page']);
        add_filter('plugin_action_links_' . plugin_basename(AB_BRICKS_AUTO_TOKEN_PLUGIN_FILE), [self::class, 'add_plugin_links']);
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links.
     * @return array
     */
    public static function add_plugin_links(array $links): array {
        $instructions_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=ab-bricks-auto-token'),
            esc_html__('Instructions', 'ab-bricks-auto-token')
        );

        array_unshift($links, $instructions_link);
        return $links;
    }

    /**
     * Add admin menu page
     *
     * @return void
     */
    public static function add_menu_page(): void {
        add_menu_page(
            __('AB Bricks Auto Token Instructions', 'ab-bricks-auto-token'),
            __('Bricks Auto Token', 'ab-bricks-auto-token'),
            'manage_options',
            'ab-bricks-auto-token',
            [self::class, 'render_page'],
            'dashicons-admin-generic',
            30
        );
    }

    /**
     * Render the instructions page
     *
     * @return void
     */
    public static function render_page(): void {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'basic';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AB Bricks Auto Token - Instructions', 'ab-bricks-auto-token'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=ab-bricks-auto-token&tab=basic"
                   class="nav-tab <?php echo $active_tab === 'basic' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Basic Usage', 'ab-bricks-auto-token'); ?>
                </a>
                <a href="?page=ab-bricks-auto-token&tab=developer"
                   class="nav-tab <?php echo $active_tab === 'developer' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Developer Guide', 'ab-bricks-auto-token'); ?>
                </a>
            </nav>

            <div class="tab-content" style="background:white;padding:20px;margin-top:20px;border:1px solid #ccd0d4;">
                <?php
                if ($active_tab === 'basic') {
                    self::render_basic_tab();
                } else {
                    self::render_developer_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render basic usage tab
     *
     * @return void
     */
    private static function render_basic_tab(): void {
        ?>
        <h2><?php esc_html_e('Basic Usage - Automatic Token & Condition Generation', 'ab-bricks-auto-token'); ?></h2>

        <h3><?php esc_html_e('Field Naming Pattern', 'ab-bricks-auto-token'); ?></h3>
        <p><?php esc_html_e('To automatically generate Bricks tokens and conditions, name your ACF or MetaBox fields using this pattern:', 'ab-bricks-auto-token'); ?></p>

        <pre style="background:#f5f5f5;padding:15px;border-left:4px solid #2271b1;font-size:14px;"><code>field_name__post_type__token
field_name__post_type__condition
field_name__post_type__token__condition</code></pre>

        <h3><?php esc_html_e('Pattern Components', 'ab-bricks-auto-token'); ?></h3>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Component', 'ab-bricks-auto-token'); ?></th>
                    <th><?php esc_html_e('Description', 'ab-bricks-auto-token'); ?></th>
                    <th><?php esc_html_e('Example', 'ab-bricks-auto-token'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>field_name</code></td>
                    <td><?php esc_html_e('The meta field name (lowercase, underscores)', 'ab-bricks-auto-token'); ?></td>
                    <td><code>sku</code>, <code>author_bio</code>, <code>is_featured</code></td>
                </tr>
                <tr>
                    <td><code>post_type</code></td>
                    <td><?php esc_html_e('The post type slug', 'ab-bricks-auto-token'); ?></td>
                    <td><code>product</code>, <code>post</code>, <code>event</code></td>
                </tr>
                <tr>
                    <td><code>__token</code></td>
                    <td><?php esc_html_e('Creates a Bricks dynamic token', 'ab-bricks-auto-token'); ?></td>
                    <td><?php esc_html_e('Results in {product_sku}', 'ab-bricks-auto-token'); ?></td>
                </tr>
                <tr>
                    <td><code>__condition</code></td>
                    <td><?php esc_html_e('Creates a Bricks condition', 'ab-bricks-auto-token'); ?></td>
                    <td><?php esc_html_e('Creates "Sku" condition', 'ab-bricks-auto-token'); ?></td>
                </tr>
            </tbody>
        </table>

        <h3><?php esc_html_e('Examples', 'ab-bricks-auto-token'); ?></h3>

        <h4><?php esc_html_e('Example 1: Token Only', 'ab-bricks-auto-token'); ?></h4>
        <div style="background:#f9f9f9;padding:15px;margin:10px 0;border-left:4px solid #00a32a;">
            <p><strong><?php esc_html_e('ACF Field Name:', 'ab-bricks-auto-token'); ?></strong> <code>author_bio__post__token</code></p>
            <p><strong><?php esc_html_e('Creates Token:', 'ab-bricks-auto-token'); ?></strong> <code>{post_author_bio}</code></p>
            <p><strong><?php esc_html_e('Available in:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('Bricks Dynamic Data → "Post (auto)" group', 'ab-bricks-auto-token'); ?></p>
        </div>

        <h4><?php esc_html_e('Example 2: Condition Only', 'ab-bricks-auto-token'); ?></h4>
        <div style="background:#f9f9f9;padding:15px;margin:10px 0;border-left:4px solid #00a32a;">
            <p><strong><?php esc_html_e('ACF Field Name:', 'ab-bricks-auto-token'); ?></strong> <code>is_featured__product__condition</code></p>
            <p><strong><?php esc_html_e('Creates Condition:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('"Is Featured"', 'ab-bricks-auto-token'); ?></p>
            <p><strong><?php esc_html_e('Available in:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('Bricks Conditions → "ab_auto_product" group', 'ab-bricks-auto-token'); ?></p>
        </div>

        <h4><?php esc_html_e('Example 3: Both Token and Condition', 'ab-bricks-auto-token'); ?></h4>
        <div style="background:#f9f9f9;padding:15px;margin:10px 0;border-left:4px solid #00a32a;">
            <p><strong><?php esc_html_e('ACF Field Name:', 'ab-bricks-auto-token'); ?></strong> <code>sku__product__token__condition</code></p>
            <p><strong><?php esc_html_e('Creates Token:', 'ab-bricks-auto-token'); ?></strong> <code>{product_sku}</code></p>
            <p><strong><?php esc_html_e('Creates Condition:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('"Sku"', 'ab-bricks-auto-token'); ?></p>
        </div>

        <h3><?php esc_html_e('Using in Bricks Builder', 'ab-bricks-auto-token'); ?></h3>

        <h4><?php esc_html_e('Using Tokens', 'ab-bricks-auto-token'); ?></h4>
        <ol>
            <li><?php esc_html_e('Edit a page/template in Bricks', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Add any element (Text, Heading, etc.)', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Click the dynamic data icon', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Look for "[Post Type] (auto)" group', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Select your auto-generated token', 'ab-bricks-auto-token'); ?></li>
        </ol>

        <h4><?php esc_html_e('Using Conditions', 'ab-bricks-auto-token'); ?></h4>
        <ol>
            <li><?php esc_html_e('Select any element in Bricks', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Go to Conditions tab', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Click "Add Condition"', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Look for "ab_auto_[post_type]" group', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Select your condition and choose comparison operator', 'ab-bricks-auto-token'); ?></li>
        </ol>

        <h3><?php esc_html_e('Supported Field Plugins', 'ab-bricks-auto-token'); ?></h3>
        <ul>
            <li><strong><?php esc_html_e('Advanced Custom Fields (ACF)', 'ab-bricks-auto-token'); ?></strong> - <?php esc_html_e('Use field name', 'ab-bricks-auto-token'); ?></li>
            <li><strong><?php esc_html_e('MetaBox', 'ab-bricks-auto-token'); ?></strong> - <?php esc_html_e('Use field ID', 'ab-bricks-auto-token'); ?></li>
        </ul>

        <h3><?php esc_html_e('Field Discovery & Caching', 'ab-bricks-auto-token'); ?></h3>

        <h4><?php esc_html_e('When New Fields Appear', 'ab-bricks-auto-token'); ?></h4>
        <p><?php esc_html_e('Fields are discovered automatically when you load the Bricks editor. After creating a new field:', 'ab-bricks-auto-token'); ?></p>
        <ol>
            <li><?php esc_html_e('Save your new ACF/MetaBox field', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Reload the Bricks editor page (hard refresh recommended: Ctrl+F5 or Cmd+Shift+R)', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Your new tokens and conditions will now be available', 'ab-bricks-auto-token'); ?></li>
        </ol>

        <h4><?php esc_html_e('Cache Behavior', 'ab-bricks-auto-token'); ?></h4>
        <p><?php esc_html_e('Currently, field caching is disabled to ensure new fields are always discovered immediately. Field discovery runs fresh on each page load. This may be optimized in future versions with proper cache invalidation.', 'ab-bricks-auto-token'); ?></p>

        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px 15px;margin:15px 0;">
            <strong><?php esc_html_e('Troubleshooting:', 'ab-bricks-auto-token'); ?></strong>
            <?php esc_html_e('If your new fields don\'t appear in Bricks, try these steps:', 'ab-bricks-auto-token'); ?>
            <ul style="margin:10px 0 0 20px;">
                <li><?php esc_html_e('Verify the field name follows the correct pattern (field_name__post_type__token)', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('Hard refresh the Bricks editor page (Ctrl+F5 or Cmd+Shift+R)', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('Check that the field is assigned to the correct post type', 'ab-bricks-auto-token'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render developer guide tab
     *
     * @return void
     */
    private static function render_developer_tab(): void {
        ?>
        <h2><?php esc_html_e('Developer Guide - Custom Integrations', 'ab-bricks-auto-token'); ?></h2>

        <h3><?php esc_html_e('Registering External Integrations', 'ab-bricks-auto-token'); ?></h3>
        <p><?php esc_html_e('You can extend this plugin by registering custom integrations for other field plugins (Pods, Toolset, Carbon Fields, etc.).', 'ab-bricks-auto-token'); ?></p>

        <h4><?php esc_html_e('Step 1: Create Integration Class', 'ab-bricks-auto-token'); ?></h4>
        <p><?php esc_html_e('Create a class that implements the IntegrationInterface or extends BaseIntegration:', 'ab-bricks-auto-token'); ?></p>

        <pre style="background:#f5f5f5;padding:15px;overflow-x:auto;"><code><?php echo esc_html('<?php
namespace YourPlugin\Integrations;

use AB\BricksAutoToken\Abstracts\BaseIntegration;

final class CustomFieldsIntegration extends BaseIntegration {

    public static function get_name(): string {
        return \'custom_fields\';
    }

    public static function is_available(): bool {
        return class_exists(\'YourCustomFieldsPlugin\');
    }

    public static function init(): void {
        if (!defined(\'BRICKS_VERSION\')) {
            return;
        }

        add_filter(\'bricks/dynamic_tags_list\', [self::class, \'register_dynamic_tags\']);
        add_filter(\'bricks/conditions/groups\', [self::class, \'register_conditionals_group\'], 1);
        add_filter(\'bricks/conditions/options\', [self::class, \'register_conditionals\'], 1);
    }

    public static function get_discovered_fields(): array {
        // Your field discovery logic
        return [];
    }

    public static function get_field_value(string $field_name, int $post_id) {
        // Your value retrieval logic
        return get_post_meta($post_id, $field_name, true);
    }
}'); ?></code></pre>

        <h4><?php esc_html_e('Step 2: Register Your Integration', 'ab-bricks-auto-token'); ?></h4>
        <p><?php esc_html_e('Use the registration hook in your plugin:', 'ab-bricks-auto-token'); ?></p>

        <pre style="background:#f5f5f5;padding:15px;overflow-x:auto;"><code><?php echo esc_html('<?php
add_action(\'ab_bricks_auto_token_register_integrations\', function() {
    AB\BricksAutoToken\IntegrationRegistry::register(
        YourPlugin\Integrations\CustomFieldsIntegration::class
    );
});'); ?></code></pre>

        <h3><?php esc_html_e('Required Methods', 'ab-bricks-auto-token'); ?></h3>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Method', 'ab-bricks-auto-token'); ?></th>
                    <th><?php esc_html_e('Description', 'ab-bricks-auto-token'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>get_name()</code></td>
                    <td><?php esc_html_e('Return unique integration name', 'ab-bricks-auto-token'); ?></td>
                </tr>
                <tr>
                    <td><code>is_available()</code></td>
                    <td><?php esc_html_e('Check if the field plugin is active', 'ab-bricks-auto-token'); ?></td>
                </tr>
                <tr>
                    <td><code>init()</code></td>
                    <td><?php esc_html_e('Register WordPress hooks for Bricks integration', 'ab-bricks-auto-token'); ?></td>
                </tr>
                <tr>
                    <td><code>get_discovered_fields()</code></td>
                    <td><?php esc_html_e('Return array of discovered fields matching the naming pattern', 'ab-bricks-auto-token'); ?></td>
                </tr>
                <tr>
                    <td><code>get_field_value()</code></td>
                    <td><?php esc_html_e('Retrieve field value for given field name and post ID', 'ab-bricks-auto-token'); ?></td>
                </tr>
            </tbody>
        </table>

        <h3><?php esc_html_e('Discovered Fields Array Format', 'ab-bricks-auto-token'); ?></h3>
        <p><?php esc_html_e('Your get_discovered_fields() method should return an array of field configurations:', 'ab-bricks-auto-token'); ?></p>

        <pre style="background:#f5f5f5;padding:15px;overflow-x:auto;"><code><?php echo esc_html('[
    \'meta_name\' => \'sku\',
    \'full_field_name\' => \'sku__product__token__condition\',
    \'post_type\' => \'product\',
    \'type\' => \'token\', // or \'condition\'
    \'field_type\' => \'text\',
    \'token_name\' => \'product_sku\',
    \'conditional_key\' => \'ab_auto_product_sku\',
    \'label\' => \'Sku\',
    \'group\' => \'Product (auto)\',
    \'group_key\' => \'ab_auto_product\',
    \'compare_options\' => [
        \'type\' => \'select\',
        \'options\' => [
            \'==\' => \'equals\',
            \'!=\' => \'not equals\',
            \'empty\' => \'is empty\',
            \'not_empty\' => \'is not empty\',
        ],
        \'placeholder\' => \'equals\',
    ],
    \'integration\' => \'your_integration_name\',
]'); ?></code></pre>

        <h3><?php esc_html_e('Helper Methods from BaseIntegration', 'ab-bricks-auto-token'); ?></h3>
        <p><?php esc_html_e('If you extend BaseIntegration, you get these helper methods:', 'ab-bricks-auto-token'); ?></p>

        <ul>
            <li><code>parse_field_pattern($field_name)</code> - <?php esc_html_e('Parse field name for pattern components', 'ab-bricks-auto-token'); ?></li>
            <li><code>format_post_type_label($post_type)</code> - <?php esc_html_e('Convert post type slug to readable label', 'ab-bricks-auto-token'); ?></li>
            <li><code>build_compare_options($field_type)</code> - <?php esc_html_e('Generate comparison operators based on field type', 'ab-bricks-auto-token'); ?></li>
            <li><code>generate_label($field_name)</code> - <?php esc_html_e('Convert field name to readable label', 'ab-bricks-auto-token'); ?></li>
        </ul>

        <h3><?php esc_html_e('Documentation', 'ab-bricks-auto-token'); ?></h3>
        <p>
            <?php esc_html_e('For detailed technical implementation details, see:', 'ab-bricks-auto-token'); ?>
            <a href="https://github.com/wpeasy/ab-bricks-auto-token/blob/master/BRICKS_AUTO.md" target="_blank">BRICKS_AUTO.md</a>
        </p>

        <h3><?php esc_html_e('Example: Built-in ACF Integration', 'ab-bricks-auto-token'); ?></h3>
        <p>
            <?php esc_html_e('See the built-in ACF integration as a reference:', 'ab-bricks-auto-token'); ?>
            <code>src/Integrations/ACF/ACFIntegration.php</code>
        </p>
        <?php
    }
}
