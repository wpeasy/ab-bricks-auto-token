<?php
/**
 * MetaBox Integration
 *
 * Handles integration with MetaBox.
 *
 * @package AB\BricksAutoToken\Integrations\MetaBox
 */

namespace AB\BricksAutoToken\Integrations\MetaBox;

defined('ABSPATH') || exit;

use AB\BricksAutoToken\Abstracts\BaseIntegration;

/**
 * MetaBox Integration class
 */
final class MetaBoxIntegration extends BaseIntegration {
    /**
     * Conditional group prefix
     */
    private const CONDITIONAL_PREFIX = 'ab_auto';

    /**
     * Get integration name
     *
     * @return string
     */
    public static function get_name(): string {
        return 'metabox';
    }

    /**
     * Check if MetaBox is available
     *
     * @return bool
     */
    public static function is_available(): bool {
        return class_exists('RWMB_Field');
    }

    /**
     * Initialize the integration
     *
     * @return void
     */
    public static function init(): void {
        // Only initialize if Bricks is active
        if (!defined('BRICKS_VERSION')) {
            return;
        }

        // Register dynamic tags
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

    /**
     * Get discovered fields from MetaBox
     *
     * @return array
     */
    public static function get_discovered_fields(): array {
        // Temporarily disable cache - will add proper invalidation later
        // if (self::$fields_cache !== null) {
        //     return self::$fields_cache;
        // }

        $discovered = [];

        if (!function_exists('rwmb_get_registry')) {
            self::$fields_cache = [];
            return self::$fields_cache;
        }

        $meta_boxes = rwmb_get_registry('meta_box')->all();

        foreach ($meta_boxes as $meta_box_id => $meta_box) {
            $fields = $meta_box->meta_box['fields'] ?? [];

            if (empty($fields)) {
                continue;
            }

            // Get post types from meta box configuration
            $post_types = self::get_meta_box_post_types($meta_box->meta_box);

            foreach ($fields as $field) {
                $field_id = $field['id'] ?? '';

                if (empty($field_id)) {
                    continue;
                }

                $parsed = self::parse_field_pattern($field_id);

                if (!$parsed) {
                    continue;
                }

                foreach ($parsed as $parsed_field) {
                    $discovered[] = [
                        'meta_name' => $parsed_field['meta_name'],
                        'full_field_name' => $field_id,
                        'post_type' => $parsed_field['post_type'],
                        'type' => $parsed_field['type'],
                        'field_type' => $field['type'] ?? 'text',
                        'token_name' => $parsed_field['post_type'] . '_' . $parsed_field['meta_name'],
                        'conditional_key' => self::CONDITIONAL_PREFIX . '_' . $parsed_field['post_type'] . '_' . $parsed_field['meta_name'],
                        'label' => self::generate_label($parsed_field['meta_name']),
                        'group' => self::format_post_type_label($parsed_field['post_type']) . ' (auto)',
                        'group_key' => self::CONDITIONAL_PREFIX . '_' . $parsed_field['post_type'],
                        'compare_options' => self::build_compare_options($field['type'] ?? 'text'),
                        'integration' => 'metabox',
                        'field_object' => $field,
                    ];

                    // Also add for configured post types
                    foreach ($post_types as $config_post_type) {
                        if ($config_post_type === $parsed_field['post_type']) {
                            continue;
                        }

                        $discovered[] = [
                            'meta_name' => $parsed_field['meta_name'],
                            'full_field_name' => $field_id,
                            'post_type' => $config_post_type,
                            'type' => $parsed_field['type'],
                            'field_type' => $field['type'] ?? 'text',
                            'token_name' => $config_post_type . '_' . $parsed_field['meta_name'],
                            'conditional_key' => self::CONDITIONAL_PREFIX . '_' . $config_post_type . '_' . $parsed_field['meta_name'],
                            'label' => self::generate_label($parsed_field['meta_name']),
                            'group' => self::format_post_type_label($config_post_type) . ' (auto)',
                            'group_key' => self::CONDITIONAL_PREFIX . '_' . $config_post_type,
                            'compare_options' => self::build_compare_options($field['type'] ?? 'text'),
                            'integration' => 'metabox',
                            'field_object' => $field,
                        ];
                    }
                }
            }
        }

        self::$fields_cache = $discovered;
        return self::$fields_cache;
    }

    /**
     * Get field value using MetaBox
     *
     * @param string $field_name Full field ID.
     * @param int    $post_id    Post ID.
     * @param string $field_type Field type (optional).
     * @return mixed
     */
    public static function get_field_value(string $field_name, int $post_id, string $field_type = '') {
        if (function_exists('rwmb_get_value')) {
            $value = rwmb_get_value($field_name, [], $post_id);
        } else {
            $value = get_post_meta($post_id, $field_name, true);
        }

        // Handle image field types - extract URL from array
        $image_types = ['single_image', 'image_advanced', 'image_upload', 'file', 'file_upload', 'file_advanced', 'image'];

        if (in_array($field_type, $image_types, true) && is_array($value)) {
            // For single_image, value is array with 'url' or 'full_url'
            if (isset($value['full_url'])) {
                return $value['full_url'];
            }
            if (isset($value['url'])) {
                return $value['url'];
            }
            // For image_advanced/gallery, it's an array of images
            if (isset($value[0]) && is_array($value[0])) {
                if (isset($value[0]['full_url'])) {
                    return $value[0]['full_url'];
                }
                if (isset($value[0]['url'])) {
                    return $value[0]['url'];
                }
            }
        }

        return $value;
    }

    /**
     * Get post types from meta box configuration
     *
     * @param array $meta_box Meta box configuration.
     * @return array
     */
    private static function get_meta_box_post_types(array $meta_box): array {
        $post_types = [];

        if (isset($meta_box['post_types'])) {
            if (is_array($meta_box['post_types'])) {
                $post_types = $meta_box['post_types'];
            } elseif (is_string($meta_box['post_types'])) {
                $post_types = [$meta_box['post_types']];
            }
        }

        return array_unique($post_types);
    }

    /**
     * Register dynamic tags with Bricks
     *
     * @param array $tags Existing tags.
     * @return array
     */
    public static function register_dynamic_tags(array $tags): array {
        $fields = self::get_discovered_fields();

        foreach ($fields as $field) {
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

    /**
     * Render a single tag
     *
     * @param mixed  $tag     Tag value.
     * @param object $post    Post object.
     * @param string $context Context (text, image, etc.).
     * @return mixed
     */
    public static function render_tag($tag, $post, string $context = 'text') {
        if (!is_object($post)) {
            return $tag;
        }

        $post_id = $post->ID ?? 0;

        if (!$post_id) {
            return $tag;
        }

        // Extract tag name without braces
        $tag_name = is_string($tag) ? trim($tag, '{}') : '';

        if (empty($tag_name)) {
            return $tag;
        }

        $fields = self::get_discovered_fields();

        foreach ($fields as $field) {
            if ($field['type'] === 'token' && $field['token_name'] === $tag_name) {
                $field_type = $field['field_type'] ?? '';

                // For image context, get the raw value (don't extract URL)
                if ($context === 'image') {
                    $value = function_exists('rwmb_get_value')
                        ? rwmb_get_value($field['full_field_name'], [], $post_id)
                        : get_post_meta($post_id, $field['full_field_name'], true);

                    // Return the value as-is for Bricks to handle
                    return $value;
                }

                // For non-image contexts, extract URL if needed
                $value = self::get_field_value($field['full_field_name'], $post_id, $field_type);
                return $value !== false ? (string) $value : '';
            }
        }

        return $tag;
    }

    /**
     * Render tags in content
     *
     * @param string $content Content with tags.
     * @param object $post    Post object.
     * @param string $context Context.
     * @return string
     */
    public static function render_content(string $content, $post, string $context = 'text'): string {
        if (!is_object($post) || empty($content)) {
            return $content;
        }

        $fields = self::get_discovered_fields();
        $token_fields = array_filter($fields, fn($f) => $f['type'] === 'token');

        foreach ($token_fields as $field) {
            $tag = '{' . $field['token_name'] . '}';

            if (str_contains($content, $tag)) {
                $value = self::get_field_value($field['full_field_name'], $post->ID, $field['field_type'] ?? '');
                $content = str_replace($tag, (string) $value, $content);
            }
        }

        return $content;
    }

    /**
     * Register conditionals group
     *
     * @param array $groups Existing groups.
     * @return array
     */
    public static function register_conditionals_group(array $groups): array {
        $fields = self::get_discovered_fields();
        $group_keys = [];

        foreach ($fields as $field) {
            if ($field['type'] === 'condition' && !in_array($field['group_key'], $group_keys, true)) {
                $groups[] = [
                    'name' => $field['group_key'],
                    'label' => $field['group'],
                ];
                $group_keys[] = $field['group_key'];
            }
        }

        return $groups;
    }

    /**
     * Register conditionals
     *
     * @param array $options Existing options.
     * @return array
     */
    public static function register_conditionals(array $options): array {
        $fields = self::get_discovered_fields();

        foreach ($fields as $field) {
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

    /**
     * Evaluate conditional
     *
     * @param bool   $result    Current result.
     * @param string $key       Conditional key.
     * @param array  $condition Condition configuration.
     * @return bool
     */
    public static function evaluate_conditional(bool $result, string $key, array $condition): bool {
        // Only handle our conditionals
        if (!str_starts_with($key, self::CONDITIONAL_PREFIX . '_')) {
            return $result;
        }

        $fields = self::get_discovered_fields();

        foreach ($fields as $field) {
            if ($field['type'] === 'condition' && $field['conditional_key'] === $key) {
                $post_id = get_the_ID();

                if (!$post_id) {
                    return false;
                }

                $value = self::get_field_value($field['full_field_name'], $post_id, $field['field_type'] ?? '');
                $compare_value = $condition['value'] ?? '';
                $operator = $condition['compare'] ?? '==';

                return self::compare_values($value, $compare_value, $operator);
            }
        }

        return $result;
    }

    /**
     * Compare values based on operator
     *
     * @param mixed  $value         Field value.
     * @param mixed  $compare_value Value to compare against.
     * @param string $operator      Comparison operator.
     * @return bool
     */
    private static function compare_values($value, $compare_value, string $operator): bool {
        switch ($operator) {
            case '==':
                return $value == $compare_value;

            case '!=':
                return $value != $compare_value;

            case 'contains':
                return is_string($value) && str_contains((string) $value, (string) $compare_value);

            case 'empty':
                return empty($value);

            case 'not_empty':
                return !empty($value);

            default:
                return false;
        }
    }
}
