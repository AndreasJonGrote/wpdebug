# WordPress Debug Log Reader

A powerful WordPress plugin for clear visualization and analysis of debug log entries.

## Features

### Auto-Refresh Functionality
- Automatically refreshes the page every 30 seconds when:
  - Browser tab is not focused OR
  - Tab is focused but inactive for 30+ seconds
- Helps monitoring log entries in background tabs
- Intelligent activity tracking (mouse, keyboard, scroll, touch)

### Debug Status Overview
- Display of current WP_DEBUG status
- Display of WP_DEBUG_LOG status
- Display of WP_DEBUG_DISPLAY status
- Display of debug log file size with warning when exceeding 2MB

### Intelligent Log Display
- Color-coded error types for quick visual recognition
- Monospace formatting for line numbers and file paths
- Grouping of related messages
- Timestamp highlighting

### Error Type Categorization
Supported error types with specific color coding:
- PHP Fatal Errors (red)
- PHP Parse Errors (red)
- PHP Warnings (orange)
- PHP Notices (orange)
- PHP Deprecated (violet)
- WordPress Database Errors (red)
- WordPress Cron Errors (red)
- Twig Template Errors (red)
- Timber Template Errors (red)
- Cache Messages (green)
- Update Messages (gray)
- Captcha Messages (turquoise)
- Uncategorized Messages

### Filtering System
- Dynamic filter checkboxes based on existing log types
- Display of entry count per category
- Easy show/hide of message types
- All entries shown by default

### Log Management
- Option to clear the log file
- Security confirmation before deletion
- Clear visual separation of filter and management functions

## Installation

1. Upload the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Access the Log Reader under "Tools > Debug Log Reader"

## Requirements

For full functionality, the following WordPress settings must be enabled:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Optional: Prevents errors from being displayed in the frontend
```

## Usage

1. Navigate to "Tools > Debug Log Reader" in your WordPress admin area
2. Debug log entries are automatically loaded and formatted
3. Use the filter checkboxes to show/hide specific message types
4. The number of entries per category is shown in parentheses
5. Use the "Clear Log File" button to empty the log file

## Security

- Only users with 'manage_options' capability can access the Log Reader
- All outputs are properly escaped
- Nonce verification when deleting the log file

## Support

For support questions or bug reports, please use the [GitHub issues page](https://github.com/yourusername/wpdebug/issues).

## License

GPL v2 or later 