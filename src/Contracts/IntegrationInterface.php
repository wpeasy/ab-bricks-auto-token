<?php
/**
 * Integration Interface
 *
 * Defines the contract that all integration modules must implement.
 *
 * @package AB\BricksAutoToken\Contracts
 */

namespace AB\BricksAutoToken\Contracts;

defined('ABSPATH') || exit;

/**
 * Interface for integration modules
 */
interface IntegrationInterface {
    /**
     * Get the integration name
     *
     * @return string
     */
    public static function get_name(): string;

    /**
     * Check if this integration is available/active
     *
     * @return bool
     */
    public static function is_available(): bool;

    /**
     * Initialize the integration
     *
     * @return void
     */
    public static function init(): void;

    /**
     * Get auto-discovered fields from this integration
     *
     * @return array
     */
    public static function get_discovered_fields(): array;

    /**
     * Get field value
     *
     * @param string $field_name Full field name/ID.
     * @param int    $post_id    Post ID.
     * @param string $field_type Field type (optional, for handling special field types like images).
     * @return mixed
     */
    public static function get_field_value(string $field_name, int $post_id, string $field_type = '');
}
