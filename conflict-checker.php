<?php
/*
Plugin Name: Plugin & Theme Conflict Checker
Plugin URI: https://metrikcorp.com
Description: A plugin to check for plugin and theme conflicts or updates.
Version: 1.1
Author: Metrikcorp
Author URI: https://metrikcorp.com
License: GPL3
*/

/**
 * Logs any PHP errors that occur while activating/deactivating plugins by checking the WordPress debug log.
 * This function looks specifically for 'PHP Fatal error' messages.
 *
 * @param string $plugin Name of the plugin being checked.
 * @return string|false Returns a conflict message if an error is found, or false if no error.
 */
function log_errors($plugin)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $errors = file_get_contents($log_file);
            if (strpos($errors, 'PHP Fatal error') !== false) {
                return "Conflict detected: $plugin caused an error.";
            }
        }
    }
    return false;
}

/**
 * Displays available plugin updates with current and recommended versions.
 */
function display_plugin_updates()
{
    // Get the list of plugins with updates
    $update_plugins = get_site_transient('update_plugins');

    if (!empty($update_plugins->response)) {
        echo '<h2>Plugin Updates Available:</h2>';
        echo '<ul>';
        foreach ($update_plugins->response as $plugin_path => $plugin_info) {
            // Get the current plugin's data
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            $current_version = $plugin_data['Version'];
            $new_version = $plugin_info->new_version;
            $plugin_name = $plugin_data['Name'];

            // Display the plugin name, current version, and new version
            echo '<li>';
            echo "Plugin: <strong>$plugin_name</strong><br>";
            echo "Current Version: $current_version<br>";
            echo "Recommended Version: $new_version";
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No plugin updates available.</p>';
    }
}

/**
 * Displays available theme updates with current and recommended versions.
 */
function display_theme_updates()
{
    $update_themes = get_site_transient('update_themes');

    if (!empty($update_themes->response)) {
        echo '<h2>Theme Updates Available:</h2>';
        echo '<ul>';
        foreach ($update_themes->response as $theme_slug => $theme_info) {
            // Get the current theme's data
            $theme = wp_get_theme($theme_slug);
            $current_version = $theme->get('Version');
            $new_version = $theme_info['new_version'];
            $theme_name = $theme->get('Name');

            // Display the theme name, current version, and new version
            echo '<li>';
            echo "Theme: <strong>$theme_name</strong><br>";
            echo "Current Version: $current_version<br>";
            echo "Recommended Version: $new_version";
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No theme updates available.</p>';
    }
}

/**
 * Runs the full conflict check by executing all detection functions.
 * This includes detecting plugin conflicts, admin notice conflicts, JavaScript errors, slow load times, and available updates.
 */
function run_full_conflict_check()
{
    detect_plugin_conflicts();
    check_for_admin_notice_conflicts();
    inject_js_error_checker();
    detect_page_load_time();

    // Show plugin and theme updates
    display_plugin_updates();
    display_theme_updates();
}

// Adds the conflict checker as a menu option in the WordPress admin dashboard
add_action('admin_menu', function () {
    add_menu_page('Conflict Checker', 'Conflict Checker', 'manage_options', 'conflict-checker', 'run_full_conflict_check');
});
?>
