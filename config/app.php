<?php
// config/app.php
// Application Configuration

// Site Information
define('SITE_NAME', 'JHUB AFRICA Project Tracker');
define('SITE_VERSION', '1.0.0');
define('SITE_URL', 'http://localhost/jhub-africa-tracker');
define('BASE_PATH', '/jhub-africa-tracker');

// Security Settings
define('SESSION_NAME', 'jhub_session');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// File Upload Settings
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif']);

// Email Settings (for later implementation)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@jhubafrica.com');
define('FROM_NAME', 'JHUB AFRICA');

// Application Settings
define('DEFAULT_TIMEZONE', 'Africa/Nairobi');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Debug mode (set to false in production)
define('DEBUG_MODE', true);

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>