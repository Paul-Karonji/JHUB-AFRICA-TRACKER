<?php
// includes/init.php
// Enhanced initialization with mentor consensus and comment moderation

// Start output buffering and session
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constants
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/jhub-africa-tracker'); // Update this for your environment

// Include configuration files
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/constants.php';

// Include core classes
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/Validator.php';

// Include helper functions
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/includes/functions.php';

// Include new consensus functions (only if file exists)
if (file_exists(ROOT_PATH . '/includes/mentor-consensus-functions.php')) {
    require_once ROOT_PATH . '/includes/mentor-consensus-functions.php';
}

// Initialize database connection using singleton pattern
try {
    $database = Database::getInstance();
} catch (Exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    
    // Show maintenance page if database connection fails
    if (!headers_sent()) {
        http_response_code(503);
        if (file_exists(ROOT_PATH . '/maintenance.html')) {
            include ROOT_PATH . '/maintenance.html';
        } else {
            echo '<h1>System Maintenance</h1><p>The system is temporarily unavailable. Please try again later.</p>';
        }
        exit;
    }
}

// Initialize authentication using singleton pattern
$auth = Auth::getInstance();

// Check for maintenance mode
if (file_exists(ROOT_PATH . '/.maintenance') && !$auth->isLoggedIn()) {
    if (!headers_sent()) {
        http_response_code(503);
        if (file_exists(ROOT_PATH . '/maintenance.html')) {
            include ROOT_PATH . '/maintenance.html';
        } else {
            echo '<h1>System Maintenance</h1><p>The system is temporarily unavailable. Please try again later.</p>';
        }
        exit;
    }
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error = "Error [{$severity}]: {$message} in {$file} on line {$line}";
    error_log($error);
    
    // Only show errors in development
    if (defined('APP_DEBUG') && APP_DEBUG && !headers_sent()) {
        echo $error;
    }
    
    return true;
});

// Global error reporting based on environment
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}

// Auto-initialize mentor approvals if needed (run once)
if (!isset($_SESSION['mentor_approvals_initialized'])) {
    if (function_exists('initializeMentorApprovals')) {
        initializeMentorApprovals();
        $_SESSION['mentor_approvals_initialized'] = true;
    }
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Set security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Only set HTTPS headers in production
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>