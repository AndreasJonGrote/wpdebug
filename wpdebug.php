<?php
/**
 * Plugin Name: WP Debug Log Reader
 * Plugin URI: https://github.com/AndreasJonGrote/wpdebug
 * Description: A professional interface to monitor and manage your WordPress debug.log file directly from the admin area.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Andreas Jon Grote
 * Author URI: https://xjonx.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpdebug
 *
 * @package WP_Debug_Reader
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPDEBUG_VERSION', '1.0.0');
define('WPDEBUG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPDEBUG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the main plugin class
require_once WPDEBUG_PLUGIN_DIR . 'includes/class-wp-debug-reader.php';

// Initialize the plugin
function wpdebug_init() {
    return WP_Debug_Reader::get_instance();
}
add_action('plugins_loaded', 'wpdebug_init');

function wpdebug_enqueue_scripts() {
    wp_enqueue_script(
        'wpdebug-auto-refresh',
        plugin_dir_url(__FILE__) . 'js/auto-refresh.js',
        array(),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'wpdebug_enqueue_scripts');
