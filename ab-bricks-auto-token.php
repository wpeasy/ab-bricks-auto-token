<?php
/**
 * Plugin Name: AB Bricks Auto Token
 * Plugin URI: https://github.com/wpeasy/ab-bricks-auto-token
 * Description: Automatically generates Bricks Builder Dynamic Tokens and Conditions based on ACF/MetaBox field naming conventions.
 * Version: 1.0.0
 * Author: WPEasy
 * Author URI: https://github.com/wpeasy
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ab-bricks-auto-token
 * Domain Path: /languages
 *
 * @package AB\BricksAutoToken
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('AB_BRICKS_AUTO_TOKEN_VERSION', '1.0.0');
define('AB_BRICKS_AUTO_TOKEN_PLUGIN_FILE', __FILE__);
define('AB_BRICKS_AUTO_TOKEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AB_BRICKS_AUTO_TOKEN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require Composer autoloader
if (file_exists(AB_BRICKS_AUTO_TOKEN_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once AB_BRICKS_AUTO_TOKEN_PLUGIN_PATH . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>AB Bricks Auto Token: Autoloader not found! Run composer install.</p></div>';
    });
    return;
}

// Initialize the plugin (use 'init' to ensure themes are loaded first)
add_action('init', function() {
    if (class_exists('AB\BricksAutoToken\Plugin')) {
        AB\BricksAutoToken\Plugin::init();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>AB Bricks Auto Token: Plugin class not found! Check autoloader.</p></div>';
        });
    }
}, 20); // Priority 20 to ensure it runs after other init hooks

// Activation hook
register_activation_hook(__FILE__, function() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('AB Bricks Auto Token requires PHP 7.4 or higher.', 'ab-bricks-auto-token'),
            esc_html__('Plugin Activation Error', 'ab-bricks-auto-token'),
            ['back_link' => true]
        );
    }

    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('AB Bricks Auto Token requires WordPress 6.0 or higher.', 'ab-bricks-auto-token'),
            esc_html__('Plugin Activation Error', 'ab-bricks-auto-token'),
            ['back_link' => true]
        );
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});
