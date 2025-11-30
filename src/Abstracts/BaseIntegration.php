<?php
/**
 * Base Integration Abstract Class
 *
 * Provides common functionality for integration modules.
 *
 * @package AB\BricksAutoToken\Abstracts
 */

namespace AB\BricksAutoToken\Abstracts;

defined('ABSPATH') || exit;

use AB\BricksAutoToken\Contracts\IntegrationInterface;

/**
 * Base class for integrations
 */
abstract class BaseIntegration implements IntegrationInterface {
    /**
     * Cache for discovered fields
     *
     * @var array|null
     */
    protected static ?array $fields_cache = null;

    /**
     * Parse field pattern to extract components
     *
     * @param string $field_name Field name or ID.
     * @return array|null Array of parsed results or null if no match.
     */
    protected static function parse_field_pattern(string $field_name): ?array {
        // Pattern: field_name__post_type__token|condition
        if (!str_contains($field_name, '__')) {
            return null;
        }

        $parts = explode('__', $field_name);

        if (count($parts) < 3) {
            return null;
        }

        $meta_name = $parts[0];
        $post_type = $parts[1];
        $indicators = array_slice($parts, 2);

        $results = [];
        $has_token = in_array('token', $indicators, true);
        $has_condition = in_array('condition', $indicators, true);

        if (!$has_token && !$has_condition) {
            return null;
        }

        if ($has_token) {
            $results[] = [
                'meta_name' => $meta_name,
                'post_type' => $post_type,
                'type' => 'token',
            ];
        }

        if ($has_condition) {
            $results[] = [
                'meta_name' => $meta_name,
                'post_type' => $post_type,
                'type' => 'condition',
            ];
        }

        return $results;
    }

    /**
     * Format post type label
     *
     * @param string $post_type Post type slug.
     * @return string
     */
    protected static function format_post_type_label(string $post_type): string {
        $post_type_obj = get_post_type_object($post_type);

        if ($post_type_obj && !empty($post_type_obj->labels->singular_name)) {
            return $post_type_obj->labels->singular_name;
        }

        return ucwords(str_replace(['_', '-'], ' ', $post_type));
    }

    /**
     * Build compare options based on field type
     *
     * @param string $field_type Field type.
     * @return array
     */
    protected static function build_compare_options(string $field_type): array {
        // Boolean fields get simplified options
        if (in_array($field_type, ['true_false', 'checkbox'], true)) {
            return [
                'type' => 'select',
                'options' => [
                    '==' => esc_html__('is true', 'ab-bricks-auto-token'),
                    '!=' => esc_html__('is false', 'ab-bricks-auto-token'),
                ],
                'placeholder' => esc_html__('is true', 'ab-bricks-auto-token'),
            ];
        }

        // All other fields get full options
        return [
            'type' => 'select',
            'options' => [
                '==' => esc_html__('equals', 'ab-bricks-auto-token'),
                '!=' => esc_html__('not equals', 'ab-bricks-auto-token'),
                'contains' => esc_html__('contains', 'ab-bricks-auto-token'),
                'empty' => esc_html__('is empty', 'ab-bricks-auto-token'),
                'not_empty' => esc_html__('is not empty', 'ab-bricks-auto-token'),
            ],
            'placeholder' => esc_html__('equals', 'ab-bricks-auto-token'),
        ];
    }

    /**
     * Generate label from field name
     *
     * @param string $field_name Field name.
     * @return string
     */
    protected static function generate_label(string $field_name): string {
        return ucwords(str_replace(['_', '-'], ' ', $field_name));
    }

    /**
     * Normalize segment for use in token/conditional names
     * Converts dashes to underscores
     *
     * @param string $segment Segment name.
     * @return string
     */
    protected static function normalize_segment(string $segment): string {
        return str_replace('-', '_', $segment);
    }

    /**
     * Clear the fields cache
     *
     * @return void
     */
    public static function clear_cache(): void {
        static::$fields_cache = null;
    }
}
