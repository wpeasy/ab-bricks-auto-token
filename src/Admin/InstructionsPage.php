<?php
/**
 * Admin Instructions Page
 *
 * @package AB\BricksAutoToken\Admin
 */

namespace AB\BricksAutoToken\Admin;

defined('ABSPATH') || exit;

use AB\BricksAutoToken\Admin\Settings;

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
        add_action('admin_init', [self::class, 'handle_form_submissions']);
        // Debug display - uncomment if needed for troubleshooting
        // add_action('admin_notices', [self::class, 'display_debug_info']);
    }

    /**
     * Display debug info about discovered fields
     *
     * @return void
     */
    public static function display_debug_info(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_ab-bricks-auto-token') {
            return;
        }

        // Force clear all caches to get fresh data
        \AB\BricksAutoToken\IntegrationRegistry::clear_all_caches();

        $active_integrations = \AB\BricksAutoToken\IntegrationRegistry::get_active_integrations();

        // Get raw fields from each integration before deduplication
        $acf_raw = [];
        $mb_raw = [];
        $mb_boxes = [];
        if (isset($active_integrations['acf'])) {
            $acf_raw = $active_integrations['acf']::get_discovered_fields();
        }
        if (isset($active_integrations['metabox'])) {
            $mb_raw = $active_integrations['metabox']::get_discovered_fields();

            // Get MetaBox meta box list for debugging
            if (function_exists('rwmb_get_registry')) {
                $all_boxes = rwmb_get_registry('meta_box')->all();
                $acf_field_boxes = [];
                $all_mb_fields = []; // Track ALL fields found in ANY meta box

                foreach ($all_boxes as $box_id => $box) {
                    $mb_boxes[] = $box->meta_box['id'] ?? $box_id;

                    // Find which boxes contain the ACF fields AND track all fields
                    $fields = $box->meta_box['fields'] ?? [];
                    foreach ($fields as $field) {
                        $field_id = $field['id'] ?? '';

                        // Track all fields with pattern
                        if (str_contains($field_id, '__')) {
                            $all_mb_fields[] = [
                                'field_id' => $field_id,
                                'box_id' => $box_id,
                                'box_name' => $box->meta_box['id'] ?? $box_id,
                            ];
                        }

                        if (in_array($field_id, ['sku__product__token__condition', 'extra_acf_image__product__token', 'extra_image__service__token'], true)) {
                            $acf_field_boxes[$box_id] = $box->meta_box['id'] ?? $box_id;
                        }
                    }
                }

                // Get fields from "service" meta box specifically
                if (isset($all_boxes['service'])) {
                    $service_box = $all_boxes['service'];
                    $service_fields = $service_box->meta_box['fields'] ?? [];
                }
            }
        }

        $all_fields = \AB\BricksAutoToken\IntegrationRegistry::get_all_discovered_fields();
        $token_fields = array_filter($all_fields, fn($f) => $f['type'] === 'token');

        ?>
        <div class="notice notice-info">
            <p><strong>Debug: Active Integrations</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($active_integrations as $name => $integration): ?>
                    <li><?php echo esc_html($name); ?>: <?php echo esc_html($integration); ?></li>
                <?php endforeach; ?>
            </ul>

            <p><strong>Debug: Raw Field Counts</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>ACF: <?php echo count($acf_raw); ?> fields (<?php echo count(array_filter($acf_raw, fn($f) => $f['type'] === 'token')); ?> tokens)</li>
                <li>MetaBox: <?php echo count($mb_raw); ?> fields (<?php echo count(array_filter($mb_raw, fn($f) => $f['type'] === 'token')); ?> tokens)</li>
            </ul>

            <?php if (!empty($mb_boxes)): ?>
                <p><strong>Debug: MetaBox Registry IDs (<?php echo count($mb_boxes); ?> total)</strong></p>
                <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 11px;">
                    <?php foreach ($mb_boxes as $box_id): ?>
                        <li><?php echo esc_html($box_id); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($all_mb_fields)): ?>
                <p><strong style="color: blue;">Debug: ALL Fields with __ pattern in MetaBox Registry (<?php echo count($all_mb_fields); ?> fields)</strong></p>
                <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 11px;">
                    <?php foreach ($all_mb_fields as $mb_field): ?>
                        <li><?php echo esc_html($mb_field['field_id']); ?> → meta box: <strong><?php echo esc_html($mb_field['box_id']); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($acf_field_boxes)): ?>
                <p><strong style="color: red;">Debug: Meta Boxes containing ACF fields (These should be filtered!)</strong></p>
                <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 11px;">
                    <?php foreach ($acf_field_boxes as $box_id => $box_name): ?>
                        <li><strong><?php echo esc_html($box_id); ?></strong> (internal ID: <?php echo esc_html($box_name); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (isset($service_fields)): ?>
                <p><strong>Debug: Fields in "service" MetaBox (<?php echo count($service_fields); ?> fields)</strong></p>
                <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 11px;">
                    <?php foreach ($service_fields as $field): ?>
                        <li><?php echo esc_html($field['id'] ?? 'no-id'); ?> (type: <?php echo esc_html($field['type'] ?? 'unknown'); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($mb_raw)): ?>
                <p><strong>Debug: MetaBox Raw Tokens</strong></p>
                <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 12px;">
                    <?php foreach ($mb_raw as $field): ?>
                        <?php if ($field['type'] === 'token'): ?>
                            <li>
                                {<?php echo esc_html($field['token_name']); ?>} from field "<?php echo esc_html($field['full_field_name']); ?>"
                                (meta box: <strong><?php echo esc_html($field['meta_box_id'] ?? 'unknown'); ?></strong>)
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><em>MetaBox discovered 0 fields</em></p>
            <?php endif; ?>

            <?php if (empty($token_fields)): ?>
                <p><em>No token fields discovered</em></p>
            <?php else: ?>
                <p><strong>Debug: Discovered Tokens (Total: <?php echo count($token_fields); ?>)</strong></p>
            <ul style="list-style: disc; margin-left: 20px; font-family: monospace; font-size: 12px;">
                <?php foreach ($token_fields as $field): ?>
                    <li>
                        <?php
                        printf(
                            '{%s} from %s field "%s" (group: %s)',
                            esc_html($field['token_name']),
                            esc_html(strtoupper($field['integration'])),
                            esc_html($field['full_field_name']),
                            esc_html($field['group'])
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle form submissions
     *
     * @return void
     */
    public static function handle_form_submissions(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle clear cache
        if (isset($_POST['ab_bricks_clear_cache']) && check_admin_referer('ab_bricks_clear_cache')) {
            \AB\BricksAutoToken\IntegrationRegistry::clear_all_caches();
            add_settings_error(
                'ab_bricks_auto_token',
                'cache_cleared',
                __('Field cache cleared successfully!', 'ab-bricks-auto-token'),
                'updated'
            );
            set_transient('ab_bricks_auto_token_admin_notice', get_settings_errors('ab_bricks_auto_token'), 30);
            wp_redirect(admin_url('admin.php?page=ab-bricks-auto-token&tab=cache'));
            exit;
        }

        // Handle TTL update
        if (isset($_POST['ab_bricks_update_ttl']) && check_admin_referer('ab_bricks_update_ttl')) {
            $ttl_preset = sanitize_key($_POST['cache_ttl_preset'] ?? 'medium');
            $custom_ttl = isset($_POST['cache_ttl_custom']) ? absint($_POST['cache_ttl_custom']) : 0;

            $ttl_map = [
                'disabled' => Settings::TTL_DISABLED,
                'short' => Settings::TTL_SHORT,
                'medium' => Settings::TTL_MEDIUM,
                'long' => Settings::TTL_LONG,
                'day' => Settings::TTL_DAY,
                'custom' => $custom_ttl,
            ];

            $ttl = $ttl_map[$ttl_preset] ?? Settings::TTL_MEDIUM;
            Settings::update_cache_ttl($ttl);

            add_settings_error(
                'ab_bricks_auto_token',
                'ttl_updated',
                __('Cache settings updated successfully!', 'ab-bricks-auto-token'),
                'updated'
            );
            set_transient('ab_bricks_auto_token_admin_notice', get_settings_errors('ab_bricks_auto_token'), 30);
            wp_redirect(admin_url('admin.php?page=ab-bricks-auto-token&tab=cache'));
            exit;
        }
    }

    /**
     * Display duplicate token warning in admin
     *
     * @return void
     */
    public static function display_duplicate_token_warning(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!\AB\BricksAutoToken\IntegrationRegistry::has_duplicate_tokens()) {
            return;
        }

        $duplicates = \AB\BricksAutoToken\IntegrationRegistry::get_duplicate_tokens();
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('AB Bricks Auto Token - Duplicate Token Detected', 'ab-bricks-auto-token'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('The following token names are used by multiple fields. Only one will work. Please rename your fields to use unique combinations of field_name and group_name.', 'ab-bricks-auto-token'); ?>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($duplicates as $token_name => $fields): ?>
                    <li>
                        <strong><?php echo esc_html('{' . $token_name . '}'); ?></strong>
                        <ul style="list-style: circle; margin-left: 20px;">
                            <?php foreach ($fields as $field): ?>
                                <li>
                                    <?php
                                    printf(
                                        /* translators: 1: integration name, 2: field name, 3: group label */
                                        esc_html__('%1$s: %2$s in %3$s', 'ab-bricks-auto-token'),
                                        '<code>' . esc_html(strtoupper($field['integration'])) . '</code>',
                                        '<code>' . esc_html($field['full_field_name']) . '</code>',
                                        '<em>' . esc_html($field['group']) . '</em>'
                                    );
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
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

        // Display admin notices
        $notices = get_transient('ab_bricks_auto_token_admin_notice');
        if ($notices) {
            delete_transient('ab_bricks_auto_token_admin_notice');
            settings_errors('ab_bricks_auto_token');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AB Bricks Auto Token - Instructions', 'ab-bricks-auto-token'); ?></h1>

            <?php if (!Settings::is_cache_enabled()): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e('Warning:', 'ab-bricks-auto-token'); ?></strong>
                        <?php esc_html_e('Field caching is currently disabled. This may impact performance on sites with many custom fields. Consider enabling cache in the Cache tab.', 'ab-bricks-auto-token'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper">
                <a href="?page=ab-bricks-auto-token&tab=basic"
                   class="nav-tab <?php echo $active_tab === 'basic' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Basic Usage', 'ab-bricks-auto-token'); ?>
                </a>
                <a href="?page=ab-bricks-auto-token&tab=developer"
                   class="nav-tab <?php echo $active_tab === 'developer' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Developer Guide', 'ab-bricks-auto-token'); ?>
                </a>
                <a href="?page=ab-bricks-auto-token&tab=cache"
                   class="nav-tab <?php echo $active_tab === 'cache' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Cache', 'ab-bricks-auto-token'); ?>
                </a>
            </nav>

            <div class="tab-content" style="background:white;padding:20px;margin-top:20px;border:1px solid #ccd0d4;">
                <?php
                if ($active_tab === 'basic') {
                    self::render_basic_tab();
                } elseif ($active_tab === 'developer') {
                    self::render_developer_tab();
                } else {
                    self::render_cache_tab();
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

        <pre style="background:#f5f5f5;padding:15px;border-left:4px solid #2271b1;font-size:14px;"><code>field_name__group_name__token
field_name__group_name__condition
field_name__group_name__token__condition</code></pre>

        <div style="background:#e7f3ff;border-left:4px solid #2271b1;padding:12px 15px;margin:15px 0;">
            <strong><?php esc_html_e('Naming Flexibility:', 'ab-bricks-auto-token'); ?></strong>
            <?php esc_html_e('You can use dashes (-) or underscores (_) in field names. Tokens will always use underscores for consistency.', 'ab-bricks-auto-token'); ?>
            <br>
            <code>my-field__product__token</code> → <code>{product_my_field}</code>
        </div>

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
                    <td><?php esc_html_e('The meta field name (can use dashes or underscores)', 'ab-bricks-auto-token'); ?></td>
                    <td><code>sku</code>, <code>author-bio</code>, <code>is_featured</code></td>
                </tr>
                <tr>
                    <td><code>group_name</code></td>
                    <td><?php esc_html_e('Grouping identifier (typically post type slug, used for UI organization only)', 'ab-bricks-auto-token'); ?></td>
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

        <h3><?php esc_html_e('Supported Field Types', 'ab-bricks-auto-token'); ?></h3>
        <ul>
            <li><?php esc_html_e('Text fields, numbers, textareas', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Boolean/checkbox fields', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Select, radio, relationship fields', 'ab-bricks-auto-token'); ?></li>
            <li><strong><?php esc_html_e('Image fields', 'ab-bricks-auto-token'); ?></strong> - <?php esc_html_e('Works with Bricks Image Element', 'ab-bricks-auto-token'); ?></li>
        </ul>

        <div style="background:#e7f3ff;border-left:4px solid #2271b1;padding:12px 15px;margin:15px 0;">
            <strong><?php esc_html_e('Important: Group Name vs Post Context', 'ab-bricks-auto-token'); ?></strong>
            <p style="margin:8px 0 0 0;"><?php esc_html_e('The group_name segment is used ONLY for organizing tokens in the Bricks UI. Field values are always read from the current post context, not determined by the group name. This allows you to use the same field name across different post types while keeping them organized in separate groups.', 'ab-bricks-auto-token'); ?></p>
        </div>

        <h3><?php esc_html_e('Field Discovery & Caching', 'ab-bricks-auto-token'); ?></h3>

        <h4><?php esc_html_e('When New Fields Appear', 'ab-bricks-auto-token'); ?></h4>
        <p><?php esc_html_e('Fields are discovered automatically when you load the Bricks editor. After creating a new field:', 'ab-bricks-auto-token'); ?></p>
        <ol>
            <li><?php esc_html_e('Save your new ACF/MetaBox field', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Wait for the cache to expire OR use the "Clear Field Cache" button in the Cache tab', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Reload the Bricks editor page (hard refresh recommended: Ctrl+F5 or Cmd+Shift+R)', 'ab-bricks-auto-token'); ?></li>
            <li><?php esc_html_e('Your new tokens and conditions will now be available', 'ab-bricks-auto-token'); ?></li>
        </ol>

        <h4><?php esc_html_e('Cache Behavior', 'ab-bricks-auto-token'); ?></h4>
        <p>
            <?php
            $cache_enabled = Settings::is_cache_enabled();
            $cache_ttl = Settings::get_cache_ttl();

            if ($cache_enabled) {
                if ($cache_ttl < 60) {
                    printf(
                        esc_html__('Field caching is enabled with a %d second cache duration. Discovered fields are stored temporarily to improve performance. If you add or modify fields, use the "Clear Field Cache" button in the Cache tab or wait for the cache to expire.', 'ab-bricks-auto-token'),
                        $cache_ttl
                    );
                } else {
                    $minutes = round($cache_ttl / 60);
                    printf(
                        esc_html__('Field caching is enabled with approximately %d minute cache duration. Discovered fields are stored temporarily to improve performance. If you add or modify fields, use the "Clear Field Cache" button in the Cache tab or wait for the cache to expire.', 'ab-bricks-auto-token'),
                        $minutes
                    );
                }
            } else {
                esc_html_e('Field caching is currently disabled. Fields are discovered fresh on every page load. For better performance, consider enabling caching in the Cache tab.', 'ab-bricks-auto-token');
            }
            ?>
        </p>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ab-bricks-auto-token&tab=cache')); ?>" class="button button-secondary">
                <?php esc_html_e('Manage Cache Settings', 'ab-bricks-auto-token'); ?>
            </a>
        </p>

        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px 15px;margin:15px 0;">
            <strong><?php esc_html_e('Troubleshooting:', 'ab-bricks-auto-token'); ?></strong>
            <?php esc_html_e('If your new fields don\'t appear in Bricks, try these steps:', 'ab-bricks-auto-token'); ?>
            <ul style="margin:10px 0 0 20px;">
                <li><?php esc_html_e('Verify the field name follows the correct pattern (field_name__group_name__token)', 'ab-bricks-auto-token'); ?></li>
                <li><strong><?php esc_html_e('Go to the Cache tab and click "Clear Field Cache" button', 'ab-bricks-auto-token'); ?></strong></li>
                <li><?php esc_html_e('Hard refresh the Bricks editor page (Ctrl+F5 or Cmd+Shift+R)', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('Check that the field is assigned to the correct post type in ACF/MetaBox settings', 'ab-bricks-auto-token'); ?></li>
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

    /**
     * Render cache management tab
     *
     * @return void
     */
    private static function render_cache_tab(): void {
        $current_ttl = Settings::get_cache_ttl();
        $preset = Settings::get_ttl_preset_name($current_ttl);
        ?>
        <h2><?php esc_html_e('Cache Management', 'ab-bricks-auto-token'); ?></h2>

        <!-- Clear Cache Section -->
        <div style="background:#f9f9f9;padding:20px;margin:20px 0;border-left:4px solid #2271b1;">
            <h3 style="margin-top:0;"><?php esc_html_e('Clear Field Cache', 'ab-bricks-auto-token'); ?></h3>
            <p><?php esc_html_e('If you have added, modified, or deleted ACF or MetaBox fields and they are not appearing in Bricks Builder, click the button below to clear the field discovery cache.', 'ab-bricks-auto-token'); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field('ab_bricks_clear_cache'); ?>
                <button type="submit" name="ab_bricks_clear_cache" class="button button-primary button-large">
                    <?php esc_html_e('Clear Field Cache Now', 'ab-bricks-auto-token'); ?>
                </button>
            </form>
        </div>

        <!-- Cache TTL Configuration -->
        <div style="background:white;padding:20px;margin:20px 0;border:1px solid #ccd0d4;">
            <h3 style="margin-top:0;"><?php esc_html_e('Cache Duration (TTL)', 'ab-bricks-auto-token'); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('ab_bricks_update_ttl'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Cache Duration', 'ab-bricks-auto-token'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="disabled" <?php checked($preset, 'disabled'); ?>>
                                    <strong><?php esc_html_e('Disabled', 'ab-bricks-auto-token'); ?></strong>
                                    <span style="color:#666;"> - <?php esc_html_e('No caching (not recommended for production)', 'ab-bricks-auto-token'); ?></span>
                                </label>
                                <br>

                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="short" <?php checked($preset, 'short'); ?>>
                                    <strong><?php esc_html_e('Short', 'ab-bricks-auto-token'); ?></strong>
                                    <span style="color:#666;"> - 5 <?php esc_html_e('seconds (development/debugging)', 'ab-bricks-auto-token'); ?></span>
                                </label>
                                <br>

                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="medium" <?php checked($preset, 'medium'); ?>>
                                    <strong><?php esc_html_e('Medium', 'ab-bricks-auto-token'); ?></strong>
                                    <span style="color:#666;"> - 20 <?php esc_html_e('seconds (recommended for development)', 'ab-bricks-auto-token'); ?></span>
                                </label>
                                <br>

                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="long" <?php checked($preset, 'long'); ?>>
                                    <strong><?php esc_html_e('Long', 'ab-bricks-auto-token'); ?></strong>
                                    <span style="color:#666;"> - 60 <?php esc_html_e('seconds (good for staging)', 'ab-bricks-auto-token'); ?></span>
                                </label>
                                <br>

                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="day" <?php checked($preset, 'day'); ?>>
                                    <strong><?php esc_html_e('1 Day', 'ab-bricks-auto-token'); ?></strong>
                                    <span style="color:#666;"> - 86,400 <?php esc_html_e('seconds (recommended for production)', 'ab-bricks-auto-token'); ?></span>
                                </label>
                                <br>

                                <label>
                                    <input type="radio" name="cache_ttl_preset" value="custom" <?php checked($preset, 'custom'); ?>>
                                    <strong><?php esc_html_e('Custom', 'ab-bricks-auto-token'); ?></strong>
                                </label>
                                <br>

                                <div id="custom_ttl_field" style="margin-left:24px;margin-top:8px;<?php echo $preset !== 'custom' ? 'display:none;' : ''; ?>">
                                    <label>
                                        <?php esc_html_e('Custom TTL (seconds):', 'ab-bricks-auto-token'); ?>
                                        <input type="number" name="cache_ttl_custom" value="<?php echo esc_attr($preset === 'custom' ? $current_ttl : 300); ?>" min="0" step="1" style="width:120px;">
                                    </label>
                                </div>

                                <script>
                                    document.querySelectorAll('input[name="cache_ttl_preset"]').forEach(function(radio) {
                                        radio.addEventListener('change', function() {
                                            var customField = document.getElementById('custom_ttl_field');
                                            if (this.value === 'custom') {
                                                customField.style.display = 'block';
                                            } else {
                                                customField.style.display = 'none';
                                            }
                                        });
                                    });
                                </script>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="ab_bricks_update_ttl" class="button button-primary">
                        <?php esc_html_e('Save Cache Settings', 'ab-bricks-auto-token'); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Information Section -->
        <div style="background:#e7f3ff;border-left:4px solid #2271b1;padding:15px;margin:20px 0;">
            <h3 style="margin-top:0;"><?php esc_html_e('About Field Caching', 'ab-bricks-auto-token'); ?></h3>

            <p><?php esc_html_e('This plugin scans your ACF and MetaBox fields to automatically create Bricks Builder tokens and conditions. Field discovery can be resource-intensive on sites with many custom fields.', 'ab-bricks-auto-token'); ?></p>

            <h4><?php esc_html_e('Why Caching Matters', 'ab-bricks-auto-token'); ?></h4>
            <p><?php esc_html_e('Without caching, the plugin scans all field groups every time you load the Bricks editor or a page that uses dynamic tokens. Caching stores the discovered fields temporarily, improving performance.', 'ab-bricks-auto-token'); ?></p>

            <h4><?php esc_html_e('Cache Invalidation', 'ab-bricks-auto-token'); ?></h4>
            <p><?php esc_html_e('Currently, neither ACF nor MetaBox provide reliable hooks to detect when fields are added, modified, or deleted. This means the cache does not automatically clear when you change fields.', 'ab-bricks-auto-token'); ?></p>

            <h4><?php esc_html_e('Recommendations', 'ab-bricks-auto-token'); ?></h4>
            <ul style="margin-left:20px;">
                <li><strong><?php esc_html_e('Production Sites:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('Use "1 Day" TTL for maximum performance. Fields rarely change on production.', 'ab-bricks-auto-token'); ?></li>
                <li><strong><?php esc_html_e('Staging/Development:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('Use "Medium" (20s) or "Long" (60s) TTL. Short enough that new fields appear quickly.', 'ab-bricks-auto-token'); ?></li>
                <li><strong><?php esc_html_e('Active Development:', 'ab-bricks-auto-token'); ?></strong> <?php esc_html_e('Use "Short" (5s) or "Disabled" while actively creating/modifying fields.', 'ab-bricks-auto-token'); ?></li>
            </ul>

            <h4><?php esc_html_e('When to Clear Cache', 'ab-bricks-auto-token'); ?></h4>
            <p><?php esc_html_e('Use the "Clear Field Cache" button above whenever:', 'ab-bricks-auto-token'); ?></p>
            <ul style="margin-left:20px;">
                <li><?php esc_html_e('You add new ACF or MetaBox fields with auto-token naming', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('You modify existing field names to follow the auto-token pattern', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('New tokens or conditions are not appearing in Bricks Builder', 'ab-bricks-auto-token'); ?></li>
                <li><?php esc_html_e('You want to force an immediate field re-scan', 'ab-bricks-auto-token'); ?></li>
            </ul>
        </div>

        <!-- Current Status -->
        <div style="background:#f0f0f0;padding:15px;margin:20px 0;border:1px solid #ddd;">
            <h3 style="margin-top:0;"><?php esc_html_e('Current Cache Status', 'ab-bricks-auto-token'); ?></h3>
            <p>
                <strong><?php esc_html_e('Cache Enabled:', 'ab-bricks-auto-token'); ?></strong>
                <?php if (Settings::is_cache_enabled()): ?>
                    <span style="color:#46b450;">✓ <?php esc_html_e('Yes', 'ab-bricks-auto-token'); ?></span>
                <?php else: ?>
                    <span style="color:#dc3232;">✗ <?php esc_html_e('No', 'ab-bricks-auto-token'); ?></span>
                <?php endif; ?>
            </p>
            <p>
                <strong><?php esc_html_e('Cache Duration:', 'ab-bricks-auto-token'); ?></strong>
                <?php
                if ($current_ttl === 0) {
                    esc_html_e('Disabled', 'ab-bricks-auto-token');
                } elseif ($current_ttl < 60) {
                    echo esc_html($current_ttl) . ' ' . esc_html__('seconds', 'ab-bricks-auto-token');
                } elseif ($current_ttl < 3600) {
                    $minutes = floor($current_ttl / 60);
                    echo esc_html($minutes) . ' ' . esc_html(_n('minute', 'minutes', $minutes, 'ab-bricks-auto-token'));
                } elseif ($current_ttl < 86400) {
                    $hours = floor($current_ttl / 3600);
                    echo esc_html($hours) . ' ' . esc_html(_n('hour', 'hours', $hours, 'ab-bricks-auto-token'));
                } else {
                    $days = floor($current_ttl / 86400);
                    echo esc_html($days) . ' ' . esc_html(_n('day', 'days', $days, 'ab-bricks-auto-token'));
                }
                ?>
            </p>
        </div>
        <?php
    }
}
