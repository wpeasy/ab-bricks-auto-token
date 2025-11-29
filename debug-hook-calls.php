<?php
/**
 * Debug Hook Calls - Add to functions.php
 *
 * This will show if Bricks is actually calling the condition hooks
 */

// Track hook calls
add_filter('bricks/conditions/groups', function($groups) {
    error_log('=== BRICKS CONDITIONS GROUPS CALLED ===');
    error_log('Input groups count: ' . count($groups));
    error_log('Input groups: ' . print_r(array_keys($groups), true));

    // Call our integration
    if (class_exists('AB\BricksAutoToken\Integrations\ACF\ACFIntegration')) {
        $groups = AB\BricksAutoToken\Integrations\ACF\ACFIntegration::register_conditionals_group($groups);
        error_log('After our filter groups count: ' . count($groups));
        error_log('After our filter groups: ' . print_r(array_keys($groups), true));
    }

    return $groups;
}, 999); // Very late priority to see final result

add_filter('bricks/conditions/options', function($options) {
    error_log('=== BRICKS CONDITIONS OPTIONS CALLED ===');
    error_log('Input options count: ' . count($options));

    // Call our integration
    if (class_exists('AB\BricksAutoToken\Integrations\ACF\ACFIntegration')) {
        $options = AB\BricksAutoToken\Integrations\ACF\ACFIntegration::register_conditionals($options);
        error_log('After our filter options count: ' . count($options));

        // Log our added options
        foreach ($options as $option) {
            if (isset($option['key']) && strpos($option['key'], 'ab_auto_') === 0) {
                error_log('Our option: ' . print_r($option, true));
            }
        }
    }

    return $options;
}, 999);

// Also add admin notice to show last hook call
add_action('admin_footer', function() {
    if (!current_user_can('manage_options')) return;

    // Read last few lines of debug.log
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($debug_log)) {
        $lines = file($debug_log);
        $last_lines = array_slice($lines, -50);

        $relevant_lines = array_filter($last_lines, function($line) {
            return strpos($line, 'BRICKS CONDITIONS') !== false ||
                   strpos($line, 'ab_auto_') !== false;
        });

        if (!empty($relevant_lines)) {
            echo '<div style="position:fixed;bottom:0;right:0;width:400px;max-height:300px;overflow:auto;background:white;border:2px solid #333;padding:10px;z-index:99999;">';
            echo '<h4>Recent Bricks Condition Hook Calls</h4>';
            echo '<pre style="font-size:10px;">' . esc_html(implode('', $relevant_lines)) . '</pre>';
            echo '</div>';
        }
    }
});
