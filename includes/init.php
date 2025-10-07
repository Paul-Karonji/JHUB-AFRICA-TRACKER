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
    
    if (DEBUG_MODE) {
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
    
    if (DEBUG_MODE) {
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