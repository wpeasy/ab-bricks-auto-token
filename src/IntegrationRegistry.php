<?php
/**
 * Integration Registry
 *
 * Manages registration and initialization of integration modules.
 *
 * @package AB\BricksAutoToken
 */

namespace AB\BricksAutoToken;

defined('ABSPATH') || exit;

use AB\BricksAutoToken\Contracts\IntegrationInterface;

/**
 * Registry for managing integration modules
 */
final class IntegrationRegistry {
    /**
     * Registered integrations
     *
     * @var array<string, string>
     */
    private static array $integrations = [];

    /**
     * Active integrations
     *
     * @var array<string, IntegrationInterface>
     */
    private static array $active_integrations = [];

    /**
     * Cache for discovered fields
     *
     * @var array|null
     */
    private static ?array $discovered_fields_cache = null;

    /**
     * Register an integration
     *
     * @param string $class_name Fully qualified class name implementing IntegrationInterface.
     * @return void
     */
    public static function register(string $class_name): void {
        if (!class_exists($class_name)) {
            return;
        }

        if (!in_array(IntegrationInterface::class, class_implements($class_name) ?: [], true)) {
            return;
        }

        $name = $class_name::get_name();
        self::$integrations[$name] = $class_name;
    }

    /**
     * Initialize all available integrations
     *
     * @return void
     */
    public static function init_integrations(): void {
        foreach (self::$integrations as $name => $class_name) {
            if ($class_name::is_available()) {
                $class_name::init();
                self::$active_integrations[$name] = $class_name;
            }
        }
    }

    /**
     * Get all discovered fields from all active integrations
     *
     * @return array
     */
    public static function get_all_discovered_fields(): array {
        if (self::$discovered_fields_cache !== null) {
            return self::$discovered_fields_cache;
        }

        $all_fields = [];

        foreach (self::$active_integrations as $integration) {
            $fields = $integration::get_discovered_fields();
            $all_fields = array_merge($all_fields, $fields);
        }

        self::$discovered_fields_cache = $all_fields;
        return self::$discovered_fields_cache;
    }

    /**
     * Get field value from appropriate integration
     *
     * @param array $field     Field configuration.
     * @param int   $post_id   Post ID.
     * @return mixed
     */
    public static function get_field_value(array $field, int $post_id) {
        $integration_name = $field['integration'] ?? '';

        if (!isset(self::$active_integrations[$integration_name])) {
            return null;
        }

        $integration = self::$active_integrations[$integration_name];
        return $integration::get_field_value($field['full_field_name'], $post_id);
    }

    /**
     * Get all active integrations
     *
     * @return array
     */
    public static function get_active_integrations(): array {
        return self::$active_integrations;
    }

    /**
     * Clear the discovered fields cache
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$discovered_fields_cache = null;
    }
}
