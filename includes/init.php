<?php
// includes/init.php
// Application Initialization

// Start output buffering
ob_start();

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
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'paultutorw@gmail.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'vjoh khga djxh vpjm'); // Gmail App Password
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'paultutorw@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'JHUB AFRICA');
if (!defined('EMAIL_ENABLED')) define('EMAIL_ENABLED', true);

// Load EmailService class
require_once __DIR__ . '/../classes/EmailService.php';


// Include helper functions
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


// Optional: Debug output control for PHPMailer
if (!defined('EMAIL_DEBUG')) define('EMAIL_DEBUG', 0); 
// 0 = no debug, 1 = client msgs, 2 = client+server msgs

// Custom exception handler
function customExceptionHandler($exception) {
    $errorMessage = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    
    if (DEBUG_MODE) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 10px; border: 1px solid #e57373; border-radius: 4px;'>";
        echo "<strong>Debug Exception:</strong> " . htmlspecialchars($errorMessage);
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        echo "<h1>System Error</h1><p>An error occurred. Please try again later.</p>";
    }
    
    // Log exception
    error_log($errorMessage, 3, __DIR__ . '/../logs/error.log');
}

// Create necessary directories if they don't exist
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../assets/uploads',
    __DIR__ . '/../assets/uploads/presentations',
    __DIR__ . '/../assets/uploads/resources',
    __DIR__ . '/../assets/uploads/profiles',
    __DIR__ . '/../assets/uploads/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        
        // Create index.php to prevent directory listing
        file_put_contents($dir . '/index.php', '<?php\n// Directory access denied\nheader("HTTP/1.1 403 Forbidden");\nexit("Access Denied");');
    }
}

?>