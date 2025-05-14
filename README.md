# WP Debug Log Reader

A WordPress plugin that provides a clean and professional interface to monitor and manage your WordPress debug.log file directly from the admin area.

## Features

- Real-time monitoring of debug.log content
- Color-coded log entries for better readability
- Automatic grouping of related log messages
- One-click log file clearing
- Visual status indicators for WP_DEBUG, WP_DEBUG_LOG, and WP_DEBUG_DISPLAY settings
- Professional formatting for different types of log entries:
  - PHP Fatal Errors (red)
  - PHP Parse Errors (red)
  - PHP Warnings (orange)
  - PHP Notices (orange)
  - PHP Deprecated notices (violet)
  - WordPress Database Errors (red)
  - Twig Runtime Errors (red)
  - Update notifications:
    - Update status messages (green)
    - Update details (gray)
  - System timestamps (blue)
  - Line numbers (monospace formatting)

## Installation

1. Upload the plugin files to `/wp-content/plugins/wpdebug` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Access the Debug Log Reader under the 'Tools' menu

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WP_DEBUG and WP_DEBUG_LOG must be enabled in wp-config.php

## Configuration

To enable debug logging, add these lines to your wp-config.php:

```php
// Enable debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Optional: Hide errors from being displayed on screen
```

## Usage

1. Navigate to Tools > Debug Log Reader in your WordPress admin area
2. View the current debug log content with color-coded entries
3. Use the "Clear Log File" button to reset the log when needed
4. Entries are automatically grouped and formatted for better readability
5. Special formatting is applied to:
   - Line numbers in error messages
   - Update process messages
   - Various PHP and WordPress error types
   - Twig template engine errors

## Features in Detail

### Error Type Highlighting
- Fatal Errors: Highlighted in red for immediate attention
- Parse Errors: Marked in red to indicate syntax problems
- Warnings: Orange highlighting for potential issues
- Notices: Orange for minor issues
- Deprecated: Violet to indicate outdated code usage
- Database Errors: Red highlighting for database issues
- Twig Errors: Red highlighting for template problems

### Update Process Tracking
- Update status messages in green
- Detailed update information in gray
- Automatic grouping of related update messages

### Code Reference Formatting
- Line numbers are formatted in monospace
- File paths and error locations are clearly visible
- Timestamps are highlighted in blue for easy reference

## Security

- Nonce verification for all actions
- Capability checking (requires 'manage_options')
- Data escaping for secure output
- Safe file operations
- Proper WordPress coding standards

## Support

For support questions or bug reports, please use the [GitHub issues page](https://github.com/yourusername/wpdebug/issues).

## License

GPL v2 or later 