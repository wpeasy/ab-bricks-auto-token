<?php
/**
 * Main Plugin Class
 *
 * @package AB\BricksAutoToken
 */

namespace AB\BricksAutoToken;

defined('ABSPATH') || exit;

/**
 * Main plugin class that initializes all functionality
 */
final class Plugin {
    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public static function init(): void {
        $instance = self::get_instance();
        $instance->setup_hooks();
        $instance->load_integrations();
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Private constructor
    }

    /**
     * Setup WordPress hooks
     *
     * @return void
     */
    private function setup_hooks(): void {
        // Load text domain for translations
        add_action('init', [$this, 'load_textdomain']);

        // Check if Bricks is active
        add_action('admin_notices', [$this, 'check_dependencies']);
    }

    /**
     * Load plugin text domain for translations
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'ab-bricks-auto-token',
            false,
            dirname(plugin_basename(AB_BRICKS_AUTO_TOKEN_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Check if required dependencies are active
     *
     * @return void
     */
    public function check_dependencies(): void {
        // Check if Bricks theme is active
        if (!defined('BRICKS_VERSION')) {
            $this->show_admin_notice(
                __('AB Bricks Auto Token requires Bricks Builder to be installed and activated.', 'ab-bricks-auto-token'),
                'warning'
            );
        }

        // Check if at least one field plugin is active
        $has_field_plugin = class_exists('ACF') || class_exists('RWMB_Field');
        if (!$has_field_plugin) {
            $this->show_admin_notice(
                __('AB Bricks Auto Token requires either Advanced Custom Fields (ACF) or MetaBox to be installed and activated.', 'ab-bricks-auto-token'),
                'warning'
            );
        }
    }

    /**
     * Show admin notice
     *
     * @param string $message Notice message.
     * @param string $type Notice type (error, warning, success, info).
     * @return void
     */
    private function show_admin_notice(string $message, string $type = 'info'): void {
        printf(
            '<div class="notice notice-%s"><p>%s</p></div>',
            esc_attr($type),
            esc_html($message)
        );
    }

    /**
     * Load integration modules
     *
     * @return void
     */
    private function load_integrations(): void {
        // Check if Bricks is active before loading integrations
        if (!defined('BRICKS_VERSION')) {
            return;
        }

        // Load MetaBox integration if MetaBox is active
        if (class_exists('RWMB_Field')) {
            // MetaBox integration will be loaded here
        }

        // Load ACF integration if ACF is active
        if (class_exists('ACF')) {
            // ACF integration will be loaded here
        }
    }
}
