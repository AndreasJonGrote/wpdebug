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

        wp_enqueue_script(
            'wpdebug-scripts',
            plugins_url('js/wpdebug.js', dirname(__FILE__)),
            array(),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'js/wpdebug.js'),
            true
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
        
        // Format Timber messages (trim spaces and color red)
        $line = preg_replace('/\[\s*Timber\s*\]/', '<span class="timber-error">[Timber]</span>', $line);
        
        // Format RB-CACHE messages
        if (strpos($line, '[RB-CACHE]') !== false) {
            $line = preg_replace('/\[RB-CACHE\]/', '<span class="cache-message">[RB-CACHE]</span>', $line);
        }
        
        // Format Cron reschedule errors (both English and German)
        $line = preg_replace('/(Cron reschedule event error|Cron-Reschedule-Event-Fehler)/', '<span class="cron-error">$1</span>', $line);
        
        // Format Captcha validation messages
        if (strpos($line, 'Validating captcha') !== false ||
            strpos($line, 'Validation result') !== false ||
            strpos($line, 'Captcha validation') !== false) {
            $line = '<span class="captcha-message">' . $line . '</span>';
        }
        
        // Format automatic update messages
        if (strpos($line, 'Automatic updates starting...') !== false ||
            strpos($line, 'Automatic plugin updates complete.') !== false ||
            strpos($line, 'Automatic updates complete.') !== false) {
            // Highlight the status message in green
            $line = preg_replace('/(?<=\<\/span\>\s)(Automatic.*(?:starting\.\.\.|complete\.))/', '<span class="auto-update-status">$1</span>', $line);
            // Make the rest of the line gray
            $line = preg_replace('/(?<=<\/span>)(.+)$/', '<span class="update-message">$1</span>', $line);
        } else if (strpos($line, 'Upgrading plugin') !== false || 
                  strpos($line, 'Plugin update failed.') !== false || 
                  strpos($line, 'Plugin updated successfully.') !== false ||
                  strpos($line, 'theme updates') !== false) {
            // Make update details gray
            $line = preg_replace('/(?<=\<\/span\>\s)(.+)$/', '<span class="update-message">$1</span>', $line);
        }
        
        // Format line numbers and file paths
        $line = preg_replace('/(on line |in )(\d+|[\/\w\-\.]+)/', '<code>$1$2</code>', $line);
        
        // Format all PHP error types in one go
        $error_types = array(
            'Parse error' => 'php-parse-error',
            'Fatal error' => 'php-fatal-error',
            'Warning' => 'php-warning',
            'Notice' => 'php-notice',
            'Deprecated' => 'php-deprecated'
        );
        
        foreach ($error_types as $type => $class) {
            $line = preg_replace('/(PHP ' . $type . '):\s*/', '<span class="wpdebug-error-type ' . $class . '">$1:</span> ', $line);
        }
        
        // Format WordPress database error
        $line = preg_replace('/(WordPress database error)\s*/', '<span class="wpdebug-error-type php-fatal-error">$1</span> ', $line);

        // Format Twig Runtime Error (both variants)
        $line = preg_replace('/((?:Next )?Twig\\\\Error\\\\RuntimeError):\s*/', '<span class="wpdebug-error-type twig-error">$1:</span> ', $line);
        
        // Format Stack Trace entries
        if (strpos($line, 'Stack trace:') !== false || preg_match('/^#\d+\s/', $line)) {
            $line = '<span class="stack-trace">' . $line . '</span>';
        }
        
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
     * Get entry type based on content
     *
     * @param string $entry The log entry
     * @return string The entry type class
     */
    private function get_entry_type($entry) {
        $types = array();
        
        // PHP Errors
        if (strpos($entry, 'PHP Fatal error') !== false) $types[] = 'type-php-fatal';
        if (strpos($entry, 'PHP Parse error') !== false) $types[] = 'type-php-parse';
        if (strpos($entry, 'PHP Warning') !== false) $types[] = 'type-php-warning';
        if (strpos($entry, 'PHP Notice') !== false) $types[] = 'type-php-notice';
        if (strpos($entry, 'PHP Deprecated') !== false) $types[] = 'type-php-deprecated';
        
        // WordPress Errors
        if (strpos($entry, 'WordPress database error') !== false) $types[] = 'type-wp-db';
        if (strpos($entry, 'Cron reschedule event error') !== false || 
            strpos($entry, 'Cron-Reschedule-Event-Fehler') !== false) {
            $types[] = 'type-wp-cron';
        }
        
        // Template Errors
        if (strpos($entry, 'Twig\Error\RuntimeError') !== false || 
            strpos($entry, 'Next Twig\Error\RuntimeError') !== false) {
            $types[] = 'type-twig';
        }
        if (strpos($entry, '[ Timber ]') !== false || 
            strpos($entry, '[Timber]') !== false) {
            $types[] = 'type-timber';
        }
        
        // Cache
        if (strpos($entry, '[RB-CACHE]') !== false) $types[] = 'type-cache';
        
        // Updates
        if (strpos($entry, 'Automatic updates') !== false ||
            strpos($entry, 'Upgrading plugin') !== false ||
            strpos($entry, 'Plugin update') !== false ||
            strpos($entry, 'theme updates') !== false) {
            $types[] = 'type-update';
        }

        // Captcha
        if (strpos($entry, 'Validating captcha') !== false ||
            strpos($entry, 'Validation result') !== false ||
            strpos($entry, 'Captcha validation') !== false) {
            $types[] = 'type-captcha';
        }
        
        // If no specific type was found, mark as unspecified
        if (empty($types)) {
            $types[] = 'type-unspecified';
        }
        
        return implode(' ', $types);
    }

    /**
     * Get available log types with labels
     *
     * @return array
     */
    private function get_log_types() {
        return array(
            'type-php-fatal' => 'PHP Fatal Errors',
            'type-php-parse' => 'PHP Parse Errors',
            'type-php-warning' => 'PHP Warnings',
            'type-php-notice' => 'PHP Notices',
            'type-php-deprecated' => 'PHP Deprecated',
            'type-wp-db' => 'WordPress Database Errors',
            'type-wp-cron' => 'WordPress Cron Errors',
            'type-twig' => 'Twig Template Errors',
            'type-timber' => 'Timber Template Errors',
            'type-cache' => 'Cache Messages',
            'type-update' => 'Update Messages',
            'type-captcha' => 'Captcha Messages',
            'type-unspecified' => 'Unspecified Messages'
        );
    }

    /**
     * Get unique types from log entries
     *
     * @param array $entries Log entries
     * @return array Array of unique type classes found in entries
     */
    private function get_unique_types_from_entries($entries) {
        $unique_types = array();
        foreach ($entries as $entry) {
            $types = $this->get_entry_type($entry);
            if (!empty($types)) {
                $entry_types = explode(' ', $types);
                $unique_types = array_merge($unique_types, $entry_types);
            }
        }
        return array_unique($unique_types);
    }

    /**
     * Count entries per type
     *
     * @param array $entries Log entries
     * @return array Associative array of type => count
     */
    private function count_entries_per_type($entries) {
        $counts = array();
        foreach ($entries as $entry) {
            $types = explode(' ', $this->get_entry_type($entry));
            foreach ($types as $type) {
                if (!isset($counts[$type])) {
                    $counts[$type] = 0;
                }
                $counts[$type]++;
            }
        }
        return $counts;
    }

    /**
     * Render filter checkboxes
     *
     * @param array $available_types Array of types that exist in the current log
     * @param array $entries Log entries for counting
     */
    private function render_filters($available_types, $entries) {
        $all_types = $this->get_log_types();
        $type_counts = $this->count_entries_per_type($entries);
        
        echo '<div class="log-filters">';
        echo '<div class="filter-checkboxes">';
        
        foreach ($all_types as $type => $label) {
            if (in_array($type, $available_types)) {
                $count = isset($type_counts[$type]) ? $type_counts[$type] : 0;
                echo '<label class="filter-checkbox">';
                echo '<input type="checkbox" class="type-filter" data-type="' . esc_attr($type) . '"> ';
                echo esc_html($label) . ' <span class="count">(' . $count . ')</span>';
                echo '</label>';
            }
        }
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Add JavaScript for filtering
     */
    private function add_filter_script() {
        // JavaScript wurde in die wpdebug.js Datei verschoben
    }

    /**
     * Format file size for display
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Render plugin page
     */
    public function render_page() {
        // Check debug status
        $debug_active = defined('WP_DEBUG') && WP_DEBUG;
        $debug_log_active = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
        $debug_display = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true;
        $log_file = WP_CONTENT_DIR . '/debug.log';
        $file_size = file_exists($log_file) ? filesize($log_file) : 0;
        $formatted_size = $this->format_file_size($file_size);
        $size_warning = $file_size >= 2097152; // 2MB in bytes
        
        echo '<div class="wrap">';
        echo '<h1>Debug Log Reader</h1>';
        
        // Show debug status
        echo '<div class="debug-status">';
        echo '<h2>Debug Status:</h2>';
        echo '<table class="widefat" style="max-width: 600px;">';
        echo '<tbody>';
        
        // WP_DEBUG Status
        echo '<tr>';
        echo '<td>WP_DEBUG</td>';
        echo '<td>' . ($debug_active ? '<span class="status-enabled">enabled</span>' : '<span class="status-disabled">disabled</span>') . '</td>';
        echo '</tr>';
        
        // WP_DEBUG_LOG Status
        echo '<tr>';
        echo '<td>WP_DEBUG_LOG</td>';
        echo '<td>' . ($debug_log_active ? '<span class="status-enabled">enabled</span>' : '<span class="status-disabled">disabled</span>') . '</td>';
        echo '</tr>';
        
        // WP_DEBUG_DISPLAY Status
        echo '<tr>';
        echo '<td>WP_DEBUG_DISPLAY</td>';
        echo '<td>' . ($debug_display ? '<span class="status-warning">enabled</span> (errors will be shown on screen)' : '<span class="status-enabled">disabled</span> (errors will only be logged)') . '</td>';
        echo '</tr>';
        
        // Log File Size
        echo '<tr>';
        echo '<td>Log File Size</td>';
        echo '<td>';
        if ($file_size > 0) {
            echo '<span class="' . ($size_warning ? 'status-warning' : 'status-enabled') . '">' . $formatted_size . '</span>';
            if ($size_warning) {
                echo ' <span class="status-warning">(Consider clearing the log file)</span>';
            }
        } else {
            echo '<span class="status-disabled">No log file</span>';
        }
        echo '</td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

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
                $available_types = $this->get_unique_types_from_entries($entries);
                
                foreach ($entries as $entry) {
                    $type_classes = $this->get_entry_type($entry);
                    echo '<div class="wpdebug-item ' . esc_attr($type_classes) . '">' . $this->format_log_line(esc_html($entry)) . '</div>';
                }
                
                echo '</div>';
                
                // Add filter checkboxes below the content
                $this->render_filters($available_types, $entries);
                
                // Add JavaScript for filtering
                $this->add_filter_script();
                
                // Button to clear log file with nonce
                echo '<div class="clear-log-section">';
                echo '<hr>';
                echo '<form method="post">';
                wp_nonce_field('wpdebug_clear_log');
                echo '<input type="submit" name="clear_log" value="Clear Log File" class="button button-primary">';
                echo '</form>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
} 