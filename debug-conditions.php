<?php
/**
 * Debug Conditions - Add to functions.php temporarily
 */

// Show discovered fields with conditions
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;

    if (class_exists('AB\BricksAutoToken\Integrations\ACF\ACFIntegration')) {
        $fields = AB\BricksAutoToken\Integrations\ACF\ACFIntegration::get_discovered_fields();

        $conditions = array_filter($fields, fn($f) => $f['type'] === 'condition');

        echo '<div class="notice notice-info" style="padding:15px;"><h3>Discovered Conditions</h3>';
        echo '<p>Total discovered fields: ' . count($fields) . '</p>';
        echo '<p>Condition fields: ' . count($conditions) . '</p>';

        if (count($conditions) > 0) {
            echo '<table border="1" cellpadding="5" style="background:white;border-collapse:collapse;">';
            echo '<tr><th>Field Name</th><th>Post Type</th><th>Conditional Key</th><th>Group Key</th><th>Label</th></tr>';
            foreach ($conditions as $c) {
                echo '<tr>';
                echo '<td>' . esc_html($c['full_field_name']) . '</td>';
                echo '<td>' . esc_html($c['post_type']) . '</td>';
                echo '<td><strong>' . esc_html($c['conditional_key']) . '</strong></td>';
                echo '<td>' . esc_html($c['group_key']) . '</td>';
                echo '<td>' . esc_html($c['label']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }
});

// Check if Bricks condition hooks are being called
add_filter('bricks/conditions/groups', function($groups) {
    error_log('AB Bricks Auto Token: bricks/conditions/groups hook fired with ' . count($groups) . ' groups');
    return $groups;
}, 1);

add_filter('bricks/conditions/options', function($options) {
    error_log('AB Bricks Auto Token: bricks/conditions/options hook fired with ' . count($options) . ' options');
    return $options;
}, 1);

// Also add admin notice to show if hooks fired
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;

    // Check if hooks are registered
    global $wp_filter;

    $hooks = [
        'bricks/conditions/groups',
        'bricks/conditions/options',
    ];

    echo '<div class="notice notice-info" style="padding:10px;"><h4>Bricks Condition Hooks Status</h4>';
    foreach ($hooks as $hook) {
        if (isset($wp_filter[$hook])) {
            $count = 0;
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                $count += count($callbacks);
            }
            echo '<p>✓ <strong>' . esc_html($hook) . '</strong>: ' . $count . ' callback(s) registered</p>';

            // Show our callback
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && isset($callback['function'][0])) {
                        $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        if (strpos($class, 'ACFIntegration') !== false) {
                            echo '<p style="margin-left:20px;">→ Priority ' . $priority . ': ACFIntegration::' . $callback['function'][1] . '</p>';
                        }
                    }
                }
            }
        } else {
            echo '<p>✗ <strong>' . esc_html($hook) . '</strong>: NOT registered</p>';
        }
    }
    echo '</div>';
});
