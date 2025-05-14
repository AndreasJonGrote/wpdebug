# WP Debug Log Reader

A WordPress plugin that provides a clean and professional interface to monitor and manage your WordPress debug.log file directly from the admin area.

## Features

- Real-time monitoring of debug.log content
- Color-coded log entries for better readability
- Automatic grouping of related log messages
- One-click log file clearing
- Visual status indicators for WP_DEBUG and WP_DEBUG_LOG settings
- Professional formatting for different types of log entries:
  - PHP Fatal Errors (red)
  - PHP Warnings (orange)
  - PHP Deprecated notices (violet)
  - WordPress Database Errors (red)
  - Update notifications (gray)
  - System timestamps (blue)

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
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Usage

1. Navigate to Tools > Debug Log Reader in your WordPress admin area
2. View the current debug log content with color-coded entries
3. Use the "Clear Log File" button to reset the log when needed
4. Entries are automatically grouped and formatted for better readability

## Security

- Nonce verification for all actions
- Capability checking (requires 'manage_options')
- Data escaping for secure output
- Safe file operations

## Support

For support questions or bug reports, please use the [GitHub issues page](https://github.com/yourusername/wpdebug/issues).

## License

GPL v2 or later 