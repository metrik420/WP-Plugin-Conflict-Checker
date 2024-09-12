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

// Enqueue CSS and JavaScript files
function enqueue_conflict_checker_assets() {
    wp_enqueue_style('conflict-checker-style', plugin_dir_url(__FILE__) . 'assets/CSS/st.css');
    wp_enqueue_script('conflict-checker-script', plugin_dir_url(__FILE__) . 'assets/js/sc.js', array(), false, true);
}
add_action('admin_enqueue_scripts', 'enqueue_conflict_checker_assets');

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
 * Detects conflicts between active plugins by deactivating and reactivating them, then checking for PHP errors.
 * If errors are detected, the conflicting plugin is reported.
 */
function detect_plugin_conflicts()
{
    $active_plugins = get_option('active_plugins');
    $conflicts = [];

    foreach ($active_plugins as $plugin) {
        deactivate_plugins($plugin);
        $error_message = log_errors($plugin);
        if ($error_message) {
            $conflicts[] = $error_message;
        }
        activate_plugin($plugin);
    }

    if (!empty($conflicts)) {
        echo '<div class="conflict-section">';
        echo '<h2>Plugin Conflicts</h2>';
        echo '<ul>';
        foreach ($conflicts as $conflict) {
            echo '<li>' . $conflict . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>No plugin conflicts detected.</p>';
    }
}

/**
 * Detects admin notices that occur when activating/deactivating plugins.
 * Admin notices can indicate conflicts or other issues triggered by plugins.
 *
 * @param string $plugin The plugin being checked.
 * @return string|false Returns a conflict message if an admin notice is detected, or false if none found.
 */
function detect_admin_notices($plugin)
{
    ob_start();
    do_action('admin_notices');
    $notices = ob_get_clean();

    if (!empty($notices)) {
        return "Admin notice detected: $plugin may be causing issues.";
    }
    return false;
}

/**
 * Checks for conflicts caused by admin notices during plugin activation.
 * This method deactivates and reactivates each plugin and checks for admin notices.
 */
function check_for_admin_notice_conflicts()
{
    $active_plugins = get_option('active_plugins');
    $admin_conflicts = [];

    foreach ($active_plugins as $plugin) {
        deactivate_plugins($plugin);
        $notice_message = detect_admin_notices($plugin);
        if ($notice_message) {
            $admin_conflicts[] = $notice_message;
        }
        activate_plugin($plugin);
    }

    if (!empty($admin_conflicts)) {
        echo '<div class="conflict-section">';
        echo '<h2>Admin Notice Conflicts</h2>';
        echo '<ul>';
        foreach ($admin_conflicts as $conflict) {
            echo '<li>' . $conflict . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>No admin notice conflicts detected.</p>';
    }
}

/**
 * Displays available plugin updates with current and recommended versions.
 */
function display_plugin_updates()
{
    $update_plugins = get_site_transient('update_plugins');

    if (!empty($update_plugins->response)) {
        echo '<div class="update-section">';
        echo '<h2>Plugin Updates</h2>';
        echo '<ul>';
        foreach ($update_plugins->response as $plugin_path => $plugin_info) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            $current_version = $plugin_data['Version'];
            $new_version = $plugin_info->new_version;
            $plugin_name = $plugin_data['Name'];

            echo '<li>';
            echo "Plugin: <strong>$plugin_name</strong><br>";
            echo "Current Version: $current_version<br>";
            echo "Recommended Version: $new_version";
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
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
        echo '<div class="update-section">';
        echo '<h2>Theme Updates</h2>';
        echo '<ul>';
        foreach ($update_themes->response as $theme_slug => $theme_info) {
            $theme = wp_get_theme($theme_slug);
            $current_version = $theme->get('Version');
            $new_version = $theme_info['new_version'];
            $theme_name = $theme->get('Name');

            echo '<li>';
            echo "Theme: <strong>$theme_name</strong><br>";
            echo "Current Version: $current_version<br>";
            echo "Recommended Version: $new_version";
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>No theme updates available.</p>';
    }
}

/**
 * Injects a JavaScript error checker into the WordPress footer.
 * This script detects any JavaScript errors on the frontend and displays them in the console and browser.
 */
function inject_js_error_checker()
{
    echo "
    <script>
    window.onerror = function(message, source, lineno, colno, error) {
        console.log('JavaScript Error detected: ' + message + ' at ' + source + ':' + lineno + ':' + colno);
        
        var errorLog = document.createElement('div');
        errorLog.style.backgroundColor = 'red';
        errorLog.style.color = 'white';
        errorLog.innerText = 'JavaScript Error detected: ' + message;
        document.body.appendChild(errorLog);
    };
    </script>";
}
add_action('wp_footer', 'inject_js_error_checker');

/**
 * Detects performance issues by measuring page load times.
 * If a page takes longer than 2 seconds to load, it logs a performance anomaly.
 */
function detect_page_load_time()
{
    $start_time = microtime(true);

    add_action('wp_footer', function () use ($start_time) {
        $end_time = microtime(true);
        $load_time = $end_time - $start_time;

        if ($load_time > 2) {
            error_log('Page load time anomaly: ' . $load_time . ' seconds');
        }
    });
}
add_action('init', 'detect_page_load_time');

/**
 * Runs the full conflict check by executing all detection functions.
 * This includes detecting plugin conflicts, admin notice conflicts, JavaScript errors, and slow load times.
 */
function run_full_conflict_check()
{
    echo '<div class="wrap">';
    echo '<h1>Conflict Checker</h1>';
    detect_plugin_conflicts();
    check_for_admin_notice_conflicts();
    display_plugin_updates();
    display_theme_updates();
    echo '</div>';
}

// Adds the conflict checker as a menu option in the WordPress admin dashboard
add_action('admin_menu', function () {
    add_menu_page('Conflict Checker', 'Conflict Checker', 'manage_options', 'conflict-checker', 'run_full_conflict_check');
});
?>
