<?php
/*
Plugin Name: Plugin & Theme Conflict Checker
Plugin URI: https://metrikcorp.com
Description: A plugin to check for plugin and theme conflicts or updates.
Version: 1.0
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
    // Check if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Define the path to the debug log file
        $log_file = WP_CONTENT_DIR . '/debug.log';

        // If the log file exists, read its content
        if (file_exists($log_file)) {
            $errors = file_get_contents($log_file);

            // Check if a 'PHP Fatal error' is present in the log file
            if (strpos($errors, 'PHP Fatal error') !== false) {
                return "Conflict detected: $plugin caused an error.";
            }
        }
    }
    return false; // Return false if no errors found
}

/**
 * Detects conflicts between active plugins by deactivating and reactivating them, then checking for PHP errors.
 * If errors are detected, the conflicting plugin is reported.
 */
function detect_plugin_conflicts()
{
    // Get the list of currently active plugins
    $active_plugins = get_option('active_plugins');
    $conflicts = []; // Array to store any detected conflicts

    // Loop through each active plugin
    foreach ($active_plugins as $plugin) {
        // Deactivate the plugin to simulate a page load without it
        deactivate_plugins($plugin);

        // Check the error log for conflicts after deactivating the plugin
        $error_message = log_errors($plugin);
        if ($error_message) {
            // Add the error message to the list of conflicts
            $conflicts[] = $error_message;
        }

        // Reactivate the plugin after testing
        activate_plugin($plugin);
    }

    // Display detected conflicts or a message if none are found
    if (!empty($conflicts)) {
        echo '<h2>Conflicts detected:</h2>';
        foreach ($conflicts as $conflict) {
            echo '<p>' . $conflict . '</p>';
        }
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
    // Capture admin notices generated during plugin activation/deactivation
    ob_start();
    do_action('admin_notices');
    $notices = ob_get_clean();

    // If there are any notices, return a conflict message
    if (!empty($notices)) {
        return "Admin notice detected: $plugin may be causing issues.";
    }
    return false; // Return false if no notices are found
}

/**
 * Checks for conflicts caused by admin notices during plugin activation.
 * This method deactivates and reactivates each plugin and checks for admin notices.
 */
function check_for_admin_notice_conflicts()
{
    // Get the list of currently active plugins
    $active_plugins = get_option('active_plugins');
    $admin_conflicts = []; // Array to store admin notice conflicts

    // Loop through each active plugin
    foreach ($active_plugins as $plugin) {
        // Deactivate the plugin to simulate a page load without it
        deactivate_plugins($plugin);

        // Check for any admin notices that may indicate a conflict
        $notice_message = detect_admin_notices($plugin);
        if ($notice_message) {
            // Add the notice message to the list of admin conflicts
            $admin_conflicts[] = $notice_message;
        }

        // Reactivate the plugin after testing
        activate_plugin($plugin);
    }

    // Display detected admin notice conflicts or a message if none are found
    if (!empty($admin_conflicts)) {
        echo '<h2>Admin notice conflicts detected:</h2>';
        foreach ($admin_conflicts as $conflict) {
            echo '<p>' . $conflict . '</p>';
        }
    } else {
        echo '<p>No admin notice conflicts detected.</p>';
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
    // Capture any JavaScript errors that occur on the page
    window.onerror = function(message, source, lineno, colno, error) {
        console.log('JavaScript Error detected: ' + message + ' at ' + source + ':' + lineno + ':' + colno);
        
        // Create a visual log of the error on the page
        var errorLog = document.createElement('div');
        errorLog.style.backgroundColor = 'red';
        errorLog.style.color = 'white';
        errorLog.innerText = 'JavaScript Error detected: ' + message;
        document.body.appendChild(errorLog);
    };
    </script>";
}
// Hook the JavaScript error checker to the footer of the site
add_action('wp_footer', 'inject_js_error_checker');

/**
 * Detects performance issues by measuring page load times.
 * If a page takes longer than 2 seconds to load, it logs a performance anomaly.
 */
function detect_page_load_time()
{
    // Capture the start time when the page starts loading
    $start_time = microtime(true);

    // Hook into the footer to calculate load time after the page fully loads
    add_action('wp_footer', function () use ($start_time) {
        $end_time = microtime(true);
        $load_time = $end_time - $start_time;

        // If the load time exceeds 2 seconds, log it as an anomaly
        if ($load_time > 2) {
            error_log('Page load time anomaly: ' . $load_time . ' seconds');
        }
    });
}
// Hook the page load time detection to the initialization process
add_action('init', 'detect_page_load_time');

/**
 * Runs the full conflict check by executing all detection functions.
 * This includes detecting plugin conflicts, admin notice conflicts, JavaScript errors, and slow load times.
 */
function run_full_conflict_check()
{
    detect_plugin_conflicts();
    check_for_admin_notice_conflicts();
    inject_js_error_checker();
    detect_page_load_time();
}

// Adds the conflict checker as a menu option in the WordPress admin dashboard
add_action('admin_menu', function () {
    add_menu_page('Conflict Checker', 'Conflict Checker', 'manage_options', 'conflict-checker', 'run_full_conflict_check');
});
?>
