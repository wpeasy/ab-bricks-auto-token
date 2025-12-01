<?php
/**
 * ACF Integration
 *
 * Handles integration with Advanced Custom Fields.
 *
 * @package AB\BricksAutoToken\Integrations\ACF
 */

namespace AB\BricksAutoToken\Integrations\ACF;

defined('ABSPATH') || exit;

use AB\BricksAutoToken\Abstracts\BaseIntegration;

/**
 * ACF Integration class
 */
final class ACFIntegration extends BaseIntegration {
    /**
     * Cache for discovered fields (override parent to prevent sharing)
     *
     * @var array|null
     */
    protected static ?array $fields_cache = null;

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
        return 'acf';
    }

    /**
     * Check if ACF is available
     *
     * @return bool
     */
    public static function is_available(): bool {
        return class_exists('ACF');
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

        // Register conditionals - use priority 1 to register very early
        add_filter('bricks/conditions/groups', [self::class, 'register_conditionals_group'], 1);
        add_filter('bricks/conditions/options', [self::class, 'register_conditionals'], 1);
        add_filter('bricks/conditions/result', [self::class, 'evaluate_conditional'], 10, 3);
    }

    /**
     * Get discovered fields from ACF
     *
     * @return array
     */
    public static function get_discovered_fields(): array {
        // Check cache first
        $cached = self::get_cached_fields();
        if ($cached !== false) {
            return $cached;
        }

        $discovered = [];
        $added_keys = []; // Track what we've already added to prevent duplicates

        if (!function_exists('acf_get_field_groups')) {
            self::save_to_cache([]);
            return [];
        }

        $field_groups = acf_get_field_groups();
        $processed_fields = []; // Track processed field keys to prevent duplicates
        $processed_groups = []; // Track processed group keys to prevent duplicates

        foreach ($field_groups as $group) {
            // Skip if we've already processed this group
            if (isset($group['key']) && in_array($group['key'], $processed_groups, true)) {
                continue;
            }
            if (isset($group['key'])) {
                $processed_groups[] = $group['key'];
            }

            $fields = acf_get_fields($group['key']);

            if (!$fields) {
                continue;
            }

            // Get post types from location rules
            $post_types = self::get_field_group_post_types($group);

            foreach ($fields as $field) {
                // Skip if we've already processed this exact field key
                if (isset($field['key']) && in_array($field['key'], $processed_fields, true)) {
                    continue;
                }
                if (isset($field['key'])) {
                    $processed_fields[] = $field['key'];
                }

                $parsed = self::parse_field_pattern($field['name']);

                if (!$parsed) {
                    continue;
                }

                foreach ($parsed as $parsed_field) {
                    // Track all post types to process (pattern post type + location post types)
                    $all_post_types = array_merge([$parsed_field['post_type']], $post_types);
                    $all_post_types = array_unique($all_post_types);

                    foreach ($all_post_types as $target_post_type) {
                        // Normalize segments for token/conditional names (convert dashes to underscores)
                        $normalized_meta = self::normalize_segment($parsed_field['meta_name']);
                        $normalized_group = self::normalize_segment($target_post_type);

                        // Create unique key for this exact field + post type + type combination
                        $unique_key = $parsed_field['type'] . '|' . $normalized_group . '|' . $normalized_meta . '|' . $field['name'];

                        if (in_array($unique_key, $added_keys, true)) {
                            continue;
                        }

                        $discovered[] = [
                            'meta_name' => $parsed_field['meta_name'],
                            'full_field_name' => $field['name'],
                            'post_type' => $target_post_type,
                            'type' => $parsed_field['type'],
                            'field_type' => $field['type'] ?? 'text',
                            'token_name' => $normalized_group . '_' . $normalized_meta,
                            'conditional_key' => self::CONDITIONAL_PREFIX . '_' . $normalized_group . '_' . $normalized_meta,
                            'label' => self::generate_label($parsed_field['meta_name']),
                            'group' => self::format_post_type_label($target_post_type) . ' (auto)',
                            'group_key' => self::CONDITIONAL_PREFIX . '_' . $normalized_group,
                            'compare_options' => self::build_compare_options($field['type'] ?? 'text'),
                            'integration' => 'acf',
                            'field_object' => $field,
                        ];
                        $added_keys[] = $unique_key;
                    }
                }
            }
        }

        // Final deduplication pass - ensure each token/condition is only added once
        $final_discovered = [];
        $final_keys = [];

        foreach ($discovered as $field) {
            // For tokens, the token_name must be unique
            // For conditions, the conditional_key must be unique
            if ($field['type'] === 'token') {
                $final_key = 'token|' . $field['token_name'];
            } else {
                $final_key = 'condition|' . $field['conditional_key'];
            }

            if (!isset($final_keys[$final_key])) {
                $final_discovered[] = $field;
                $final_keys[$final_key] = true;
            }
        }

        self::save_to_cache($final_discovered);
        return $final_discovered;
    }

    /**
     * Get field value using ACF
     *
     * @param string $field_name Full field name.
     * @param int    $post_id    Post ID.
     * @param string $field_type Field type (optional).
     * @return mixed
     */
    public static function get_field_value(string $field_name, int $post_id, string $field_type = '') {
        if (function_exists('get_field')) {
            $value = get_field($field_name, $post_id);
        } else {
            $value = get_post_meta($post_id, $field_name, true);
        }

        // Handle image field types - extract URL from array
        $image_types = ['image', 'gallery', 'file'];

        if (in_array($field_type, $image_types, true) && is_array($value)) {
            // For image field set to return array
            if (isset($value['url'])) {
                return $value['url'];
            }
            // For gallery, it's an array of images
            if (isset($value[0]) && is_array($value[0]) && isset($value[0]['url'])) {
                return $value[0]['url'];
            }
            // If value is numeric (attachment ID), get the URL
            if (is_numeric($value)) {
                $url = wp_get_attachment_url($value);
                return $url ?: $value;
            }
        }

        // Handle numeric values that might be attachment IDs for image types
        if (in_array($field_type, $image_types, true) && is_numeric($value)) {
            $url = wp_get_attachment_url($value);
            return $url ?: $value;
        }

        return $value;
    }

    /**
     * Get post types from field group location rules
     *
     * @param array $field_group Field group configuration.
     * @return array
     */
    private static function get_field_group_post_types(array $field_group): array {
        $post_types = [];

        if (empty($field_group['location'])) {
            return $post_types;
        }

        foreach ($field_group['location'] as $location_group) {
            foreach ($location_group as $rule) {
                if ($rule['param'] === 'post_type' && $rule['operator'] === '==') {
                    $post_types[] = $rule['value'];
                }
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

                // For image context, return attachment ID as array
                if ($context === 'image') {
                    $value = function_exists('get_field')
                        ? get_field($field['full_field_name'], $post_id)
                        : get_post_meta($post_id, $field['full_field_name'], true);

                    $attachment_id = null;

                    // Handle different ACF return formats:
                    // 1. Image Array (default): array with ID, url, sizes, etc.
                    // 2. Image ID: numeric value
                    // 3. Image URL: string URL (need to get ID from URL)

                    if (is_array($value)) {
                        // Image Array format
                        if (isset($value['ID'])) {
                            $attachment_id = $value['ID'];
                        } elseif (isset($value['id'])) {
                            $attachment_id = $value['id'];
                        } elseif (isset($value[0]) && is_array($value[0])) {
                            // Gallery format
                            if (isset($value[0]['ID'])) {
                                $attachment_id = $value[0]['ID'];
                            } elseif (isset($value[0]['id'])) {
                                $attachment_id = $value[0]['id'];
                            }
                        }
                    } elseif (is_numeric($value)) {
                        // Image ID format
                        $attachment_id = $value;
                    } elseif (is_string($value) && !empty($value)) {
                        // Image URL format - need to get the attachment ID from the URL
                        $attachment_id = attachment_url_to_postid($value);
                    }

                    // Verify attachment exists and return as ARRAY (Bricks expects array)
                    if ($attachment_id && wp_attachment_is_image($attachment_id)) {
                        return [ (int) $attachment_id ];
                    }

                    return [];
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

        foreach ($fields as $field) {
            if ($field['type'] === 'condition' && !self::group_exists($groups, $field['group_key'])) {
                $groups[] = [
                    'name' => $field['group_key'],
                    'label' => $field['group'],
                ];
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
