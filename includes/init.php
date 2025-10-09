<?php
// includes/init.php
// Application Initialization - FIXED VERSION (No Function Duplication)

// Start output buffering
ob_start();

// ✅ CRITICAL FIX: Load Composer autoloader FIRST (for PHPMailer and other dependencies)
require_once __DIR__ . '/../vendor/autoload.php';

// Include configuration files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/constants.php';

// Include core classes
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Validator.php';

// --- Email Configuration (Final Production Setup) ---
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'info.jhub@jkuat.ac.ke');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', ''); // Gmail App Password
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'info.jhub@jkuat.ac.ke');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'JHUB AFRICA');
if (!defined('EMAIL_ENABLED')) define('EMAIL_ENABLED', true);
if (!defined('EMAIL_DEBUG')) define('EMAIL_DEBUG', 0);

if (!defined('ADMIN_NOTIFICATION_EMAIL')) define('ADMIN_NOTIFICATION_EMAIL', 'info.jhub@jkuat.ac.ke');
if (!defined('EMAIL_TEMPLATES_DIR')) define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/emails/');

if (!defined('ADMIN_NOTIFICATION_EMAIL')) define('ADMIN_NOTIFICATION_EMAIL', 'info.jhub@jkuat.ac.ke');

// Load EmailService class (now PHPMailer will be available)
require_once __DIR__ . '/../classes/EmailService.php';

// Include helper functions (these files contain the flash message functions)
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email-functions.php';

// Initialize authentication
$auth = Auth::getInstance();
$auth->startSession();

// Create database connection
$database = Database::getInstance();

// Set global error handler
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Custom error handler
function customErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    $errorMessage = "Error [{$severity}]: {$message} in {$file} on line {$line}";
    
    // ✅ FIX FOR ERROR #2: Don't output HTML in API/JSON contexts
    $isApiRequest = (
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
    
    if (DEBUG_MODE && !$isApiRequest) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 10px; border: 1px solid #e57373; border-radius: 4px;'>";
        echo "<strong>Debug Error:</strong> " . htmlspecialchars($errorMessage);
        echo "</div>";
    }
    
    // Log error
    error_log($errorMessage, 3, __DIR__ . '/../logs/error.log');
}

// Custom exception handler
function customExceptionHandler($exception) {
    $errorMessage = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    
    // ✅ FIX FOR ERROR #2: Don't output HTML in API/JSON contexts
    $isApiRequest = (
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
    
    if (DEBUG_MODE && !$isApiRequest) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 10px; border: 1px solid #e57373; border-radius: 4px;'>";
        echo "<strong>Debug Exception:</strong> " . htmlspecialchars($errorMessage);
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    // Log exception
    error_log($errorMessage, 3, __DIR__ . '/../logs/error.log');
}

// Initialize session data arrays if not exists
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [];
}

if (!isset($_SESSION['old_input'])) {
    $_SESSION['old_input'] = [];
}

// ✅ NOTE: Flash message functions (setFlashMessage, getFlashMessages, displayFlashMessages) 
// are already defined in helpers.php - no need to duplicate them here
?>