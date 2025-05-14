<?php
/**
 * Main plugin class
 *
 * @package WP_Debug_Reader
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Debug_Reader {
    /**
     * Plugin instance
     *
     * @var WP_Debug_Reader
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WP_Debug_Reader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Add menu item
     */
    public function add_menu() {
        add_management_page(
            'Debug Log Reader',
            'Debug Log Reader',
            'manage_options',
            'wpdebug-reader',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles($hook) {
        if ('tools_page_wpdebug-reader' !== $hook) {
            return;
        }
        wp_enqueue_style(
            'wpdebug-styles',
            plugins_url('assets/css/wpdebug-styles.css', dirname(__FILE__)),
            array(),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/wpdebug-styles.css')
        );
    }

    /**
     * Handle admin notices
     */
    public function admin_notices() {
        $notice = get_option('wpdebug_notice');
        if ($notice) {
            ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
            <?php
            delete_option('wpdebug_notice');
        }
    }

    /**
     * Handle actions
     */
    public function handle_actions() {
        if (isset($_POST['clear_log']) && check_admin_referer('wpdebug_clear_log')) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                if (file_put_contents($log_file, '') !== false) {
                    update_option('wpdebug_notice', array(
                        'message' => 'The debug log file has been cleared successfully.',
                        'type' => 'success'
                    ));
                } else {
                    update_option('wpdebug_notice', array(
                        'message' => 'Could not clear the debug log file.',
                        'type' => 'error'
                    ));
                }
            }
            wp_safe_redirect(add_query_arg('page', 'wpdebug-reader', admin_url('tools.php')));
            exit;
        }
    }

    /**
     * Format log line
     *
     * @param string $line Log line
     * @return string
     */
    private function format_log_line($line) {
        // Format timestamp
        $line = preg_replace('/\[([\d]{2}-[A-Za-z]{3}-[\d]{4}\s[\d]{2}:[\d]{2}:[\d]{2}\sUTC)\]\s*/', '<span class="wpdebug_timestamp">[$1]</span> ', $line);
        
        // Format double bracket markings after timestamp
        $line = preg_replace('/(?<=\<\/span\>\s)(\[[^\]]+\])\s*(\[[^\]]+\])\s*/', '<span class="wptransfer-info">$1 $2</span> ', $line);
        
        // Format PHP Fatal Error
        $line = preg_replace('/(PHP Fatal error):\s*/', '<span class="wpdebug-error-type php-fatal-error">$1:</span> ', $line);
        
        // Format PHP Warning
        $line = preg_replace('/(PHP Warning):\s*/', '<span class="wpdebug-error-type php-warning">$1:</span> ', $line);
        
        // Format PHP Deprecated
        $line = preg_replace('/(PHP Deprecated):\s*/', '<span class="wpdebug-error-type php-deprecated">$1:</span> ', $line);
        
        // Format WordPress database error
        $line = preg_replace('/(WordPress database error)\s*/', '<span class="wpdebug-error-type php-fatal-error">$1</span> ', $line);
        
        return $line;
    }

    /**
     * Parse log content
     *
     * @param string $content Log content
     * @return array
     */
    private function parse_log_content($content) {
        $entries = array();
        $current_entry = '';
        $lines = explode("\n", $content);
        $in_update_block = false;
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Check for update block start
            if (strpos($line, 'Automatic updates starting...') !== false) {
                if (!empty($current_entry)) {
                    $entries[] = trim($current_entry);
                }
                $current_entry = $line;
                $in_update_block = true;
                continue;
            }
            
            // If we're in an update block
            if ($in_update_block) {
                // Check if update block ends
                if (strpos($line, 'Automatic updates complete.') !== false) {
                    $current_entry .= "\n" . $line;
                    $entries[] = trim($current_entry);
                    $current_entry = '';
                    $in_update_block = false;
                    continue;
                }
                $current_entry .= "\n" . $line;
                continue;
            }
            
            // Normal log entry
            if (preg_match('/^\[[\d]{2}-[A-Za-z]{3}-[\d]{4}\s[\d]{2}:[\d]{2}:[\d]{2}\sUTC\]/', $line)) {
                if (!empty($current_entry)) {
                    $entries[] = trim($current_entry);
                }
                $current_entry = $line;
            } else {
                $current_entry .= "\n" . $line;
            }
        }
        
        // Don't forget the last entry
        if (!empty($current_entry)) {
            $entries[] = trim($current_entry);
        }
        
        return $entries;
    }

    /**
     * Render plugin page
     */
    public function render_page() {
        // Check debug status
        $debug_active = defined('WP_DEBUG') && WP_DEBUG;
        $debug_log_active = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
        $debug_display = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true;
        
        echo '<div class="wrap">';
        echo '<h1>Debug Log Reader</h1>';
        
        // Show debug status
        echo '<div class="debug-status">
            <h2>Debug Status</h2>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><strong>Setting</strong></th>
                        <th><strong>Status</strong></th>
                        <th><strong>Note</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WP_DEBUG</td>
                        <td>' . ($debug_active ? '<span class="status-enabled">enabled</span>' : '<span class="status-disabled">disabled</span>') . '</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>WP_DEBUG_LOG</td>
                        <td>' . ($debug_log_active ? '<span class="status-enabled">enabled</span>' : '<span class="status-disabled">disabled</span>') . '</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>WP_DEBUG_DISPLAY</td>
                        <td>' . ($debug_display ? '<span class="status-warning">enabled</span>' : '<span class="status-enabled">disabled</span>') . '</td>
                        <td>' . ($debug_display ? 'Errors are displayed on screen' : 'Errors are only logged') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>';

        if (!$debug_active || !$debug_log_active) {
            echo '<div class="notice notice-warning inline"><p>';
            echo '<strong>Debug logging is not fully configured:</strong><br>';
            echo 'To enable debug logging, add these lines to your wp-config.php:<br>';
            echo '<code class="debug-code-block">// Enable debug logging<br>';
            echo 'define(\'WP_DEBUG\', true);<br>';
            echo 'define(\'WP_DEBUG_LOG\', true);<br>';
            echo 'define(\'WP_DEBUG_DISPLAY\', false); // Optional: Hide errors from being displayed</code>';
            echo '</p></div>';
            return;
        }
        
        // Read debug.log if enabled
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            echo '<div class="notice notice-warning inline"><p>';
            echo '<strong>No debug.log file found!</strong><br>';
            echo 'The debug.log file will be automatically created in <code>' . esc_html(WP_CONTENT_DIR) . '</code> when the first error occurs.<br>';
            echo 'If the file is not being created, check the directory permissions.';
            echo '</p></div>';
            return;
        }
            
        if (file_exists($log_file)) {
            echo '<div class="log-content">';
            echo '<h2>Debug Log Content:</h2>';
            
            $log_content = file_get_contents($log_file);
            
            if (empty(trim($log_content))) {
                echo '<div class="notice notice-info inline"><p>';
                echo 'The debug.log file exists but is empty. New errors will be logged here when they occur.';
                echo '</p></div>';
            } else {
                echo '<div class="log-content-container">';
                
                $entries = $this->parse_log_content($log_content);
                
                foreach ($entries as $entry) {
                    echo '<div class="wpdebug-item">' . $this->format_log_line(esc_html($entry)) . '</div>';
                }
                
                echo '</div>';
                
                // Button to clear log file with nonce
                echo '<form method="post" style="margin-top: 15px;">';
                wp_nonce_field('wpdebug_clear_log');
                echo '<input type="submit" name="clear_log" value="Clear Log File" class="button button-primary">';
                echo '</form>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
} 