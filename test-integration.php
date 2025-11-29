<?php
/**
 * Integration Test - Add to functions.php temporarily
 *
 * This will show debug info in WordPress admin
 */

// Test if our plugin is loaded
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="notice notice-info"><h3>AB Bricks Auto Token - Debug Info</h3>';

    // Check plugin is loaded
    echo '<p><strong>Plugin Loaded:</strong> ';
    if (defined('AB_BRICKS_AUTO_TOKEN_VERSION')) {
        echo '✓ YES (v' . AB_BRICKS_AUTO_TOKEN_VERSION . ')';
    } else {
        echo '✗ NO - Plugin not loaded!';
    }
    echo '</p>';

    // Check dependencies
    echo '<p><strong>ACF Active:</strong> ' . (class_exists('ACF') ? '✓ YES' : '✗ NO') . '</p>';
    echo '<p><strong>Bricks Active:</strong> ' . (defined('BRICKS_VERSION') ? '✓ YES (v' . BRICKS_VERSION . ')' : '✗ NO') . '</p>';

    // Check our classes exist
    echo '<p><strong>Plugin Class:</strong> ' . (class_exists('AB\BricksAutoToken\Plugin') ? '✓ YES' : '✗ NO') . '</p>';
    echo '<p><strong>ACF Integration:</strong> ' . (class_exists('AB\BricksAutoToken\Integrations\ACF\ACFIntegration') ? '✓ YES' : '✗ NO') . '</p>';

    // Check if ACF integration is available
    if (class_exists('AB\BricksAutoToken\Integrations\ACF\ACFIntegration')) {
        $available = AB\BricksAutoToken\Integrations\ACF\ACFIntegration::is_available();
        echo '<p><strong>ACF Integration Available:</strong> ' . ($available ? '✓ YES' : '✗ NO') . '</p>';

        if ($available) {
            // Get discovered fields
            $fields = AB\BricksAutoToken\Integrations\ACF\ACFIntegration::get_discovered_fields();
            echo '<p><strong>Discovered Fields:</strong> ' . count($fields) . '</p>';

            if (!empty($fields)) {
                echo '<table border="1" cellpadding="5" style="border-collapse:collapse;background:white;">';
                echo '<tr><th>Full Field Name</th><th>Post Type</th><th>Type</th><th>Token/Key</th></tr>';
                foreach ($fields as $field) {
                    echo '<tr>';
                    echo '<td>' . esc_html($field['full_field_name']) . '</td>';
                    echo '<td>' . esc_html($field['post_type']) . '</td>';
                    echo '<td>' . esc_html($field['type']) . '</td>';
                    echo '<td><strong>' . esc_html($field['type'] === 'token' ? '{' . $field['token_name'] . '}' : $field['conditional_key']) . '</strong></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p style="color:red;"><strong>NO FIELDS DISCOVERED!</strong></p>';
                echo '<p>Checking ACF field groups...</p>';

                if (function_exists('acf_get_field_groups')) {
                    $groups = acf_get_field_groups();
                    echo '<p>Found ' . count($groups) . ' ACF field groups:</p>';

                    foreach ($groups as $group) {
                        echo '<div style="border:1px solid #ccc;padding:10px;margin:5px 0;background:white;">';
                        echo '<strong>' . esc_html($group['title']) . '</strong><br>';

                        $fields_in_group = acf_get_fields($group['key']);
                        if ($fields_in_group) {
                            echo 'Fields: ';
                            foreach ($fields_in_group as $f) {
                                $matches = preg_match('/^[a-z_]+__[a-z_]+__(token|condition)/', $f['name']);
                                echo '<span style="' . ($matches ? 'color:green;font-weight:bold;' : '') . '">';
                                echo esc_html($f['name']);
                                if ($matches) echo ' ✓';
                                echo '</span>, ';
                            }
                        }
                        echo '</div>';
                    }
                }
            }
        }
    }

    // Check active integrations
    if (class_exists('AB\BricksAutoToken\IntegrationRegistry')) {
        $active = AB\BricksAutoToken\IntegrationRegistry::get_active_integrations();
        echo '<p><strong>Active Integrations:</strong> ' . count($active) . '</p>';
        if (!empty($active)) {
            foreach ($active as $name => $class) {
                echo '<p style="margin-left:20px;">- ' . esc_html($name) . '</p>';
            }
        }
    }

    // Check if hooks are registered
    global $wp_filter;
    $hooks = [
        'bricks/dynamic_tags_list',
        'bricks/conditions/groups',
        'bricks/conditions/options',
    ];

    echo '<p><strong>Registered Hooks:</strong></p>';
    foreach ($hooks as $hook) {
        $has_callbacks = isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks);
        echo '<p style="margin-left:20px;">- ' . esc_html($hook) . ': ' . ($has_callbacks ? '✓ YES' : '✗ NO') . '</p>';
    }

    echo '</div>';
});
