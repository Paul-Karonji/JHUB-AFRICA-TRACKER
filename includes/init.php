<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Application Initialization File
 * 
 * This file initializes the entire application by loading all necessary
 * configurations, classes, and setting up the environment.
 * 
 * Include this file at the top of every PHP file that needs access
 * to the application framework.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'init.php') {
    die('Direct access not permitted');
}

// Start output buffering
ob_start();

// Define application access constant
define('JHUB_ACCESS', true);

// Get the root directory
define('ROOT_DIR', dirname(dirname(__FILE__)) . '/');

// ==============================================
// ERROR HANDLING SETUP
// ==============================================

// Custom error handler
function jhubErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING', 
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
    ];
    
    $errorType = $errorTypes[$severity] ?? 'UNKNOWN ERROR';
    $errorMessage = "[$errorType] $message in $file on line $line";
    
    // Log error
    error_log($errorMessage);
    
    // Don't execute PHP internal error handler
    return true;
}

// Custom exception handler
function jhubExceptionHandler($exception) {
    $message = sprintf(
        "Uncaught exception '%s' with message '%s' in %s:%d\nStack trace:\n%s",
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($message);
    
    // Show user-friendly error in production
    if (!defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE) {
        http_response_code(500);
        include ROOT_DIR . 'templates/error.php';
        exit;
    }
    
    // Show detailed error in development
    echo '<h1>Application Error</h1>';
    echo '<pre>' . htmlspecialchars($message) . '</pre>';
    exit;
}

// Set custom error and exception handlers
set_error_handler('jhubErrorHandler');
set_exception_handler('jhubExceptionHandler');

// ==============================================
// LOAD CONFIGURATION FILES
// ==============================================

try {
    // Load constants first
    require_once ROOT_DIR . 'config/constants.php';
    
    // Load database configuration
    require_once ROOT_DIR . 'config/database.php';
    
    // Load application configuration
    require_once ROOT_DIR . 'config/app.php';
    
} catch (Exception $e) {
    die('Configuration loading failed: ' . $e->getMessage());
}

// ==============================================
// LOAD CORE CLASSES
// ==============================================

// Auto-loader for classes
spl_autoload_register(function($className) {
    $classFile = ROOT_DIR . 'classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    return false;
});

// Load core classes manually (for dependency order)
$coreClasses = [
    'Database',
    'Validator',
    'Auth',
    'Project',
    'Comment',
    'Rating',
    'Mentor',
    'Notification'
];

foreach ($coreClasses as $class) {
    $classFile = ROOT_DIR . 'classes/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    } else {
        error_log("Warning: Core class file not found: $classFile");
    }
}

// ==============================================
// LOAD HELPER FILES
// ==============================================

$helperFiles = [
    'functions.php',
    'helpers.php',
    'session.php'
];

foreach ($helperFiles as $file) {
    $filePath = ROOT_DIR . 'includes/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        error_log("Warning: Helper file not found: $filePath");
    }
}

// ==============================================
// ENVIRONMENT SETUP
// ==============================================

// Set development mode flag
define('DEVELOPMENT_MODE', AppConfig::isDevelopment());

// Initialize environment settings
DatabaseConfig::initializeEnvironment();

// Initialize session
AppConfig::initializeSession();

// Set up timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// ==============================================
// DATABASE CONNECTION TEST
// ==============================================

try {
    $db = Database::getInstance();
    // Test connection
    $db->prepare("SELECT 1")->execute();
} catch (Exception $e) {
    error_log("Database connection failed during initialization: " . $e->getMessage());
    
    if (DEVELOPMENT_MODE) {
        die("Database connection failed. Please check your configuration.");
    } else {
        // Redirect to maintenance page in production
        http_response_code(503);
        include ROOT_DIR . 'templates/maintenance.php';
        exit;
    }
}

// ==============================================
// SECURITY SETUP
// ==============================================

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif ($_SESSION['last_regeneration'] < (time() - 300)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Set security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    if (AppConfig::isProduction()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'");
    }
}

// Apply security headers for web pages (not for API endpoints)
if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') !== 0) {
    setSecurityHeaders();
}

// ==============================================
// UTILITY FUNCTIONS
// ==============================================

/**
 * Check if request is AJAX
 * 
 * @return bool True if AJAX request
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 * 
 * @return bool True if POST request
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 * 
 * @return bool True if GET request
 */
function isGetRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Get client IP address
 * 
 * @return string Client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Log application activity
 * 
 * @param string $level Log level (INFO, WARNING, ERROR, etc.)
 * @param string $message Log message
 * @param array $context Additional context data
 */
function logActivity($level, $message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $url = getCurrentUrl();
    
    $contextString = '';
    if (!empty($context)) {
        $contextString = ' | Context: ' . json_encode($context);
    }
    
    $logMessage = "[$timestamp] [$level] $message | IP: $ip | URL: $url | User-Agent: $userAgent$contextString";
    
    // Write to log file
    error_log($logMessage, 3, ROOT_DIR . 'logs/app.log');
}

// ==============================================
// APPLICATION READY FLAG
// ==============================================

// Set application as initialized
define('JHUB_INITIALIZED', true);

// Log successful initialization (only in development)
if (DEVELOPMENT_MODE) {
    logActivity('INFO', 'Application initialized successfully');
}

// ==============================================
// DEVELOPMENT HELPERS
// ==============================================

if (DEVELOPMENT_MODE) {
    /**
     * Debug dump function
     * 
     * @param mixed $data Data to dump
     * @param bool $die Whether to die after dumping
     */
    function dd($data, $die = true) {
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; margin: 10px; border-radius: 5px;">';
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            var_dump($data);
        }
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
    
    /**
     * Debug function for development
     * 
     * @param mixed $data Data to debug
     * @param string $label Optional label
     */
    function debug($data, $label = null) {
        $output = '';
        if ($label) {
            $output .= "<strong>$label:</strong> ";
        }
        
        if (is_array($data) || is_object($data)) {
            $output .= '<pre>' . print_r($data, true) . '</pre>';
        } else {
            $output .= '<pre>' . var_export($data, true) . '</pre>';
        }
        
        echo '<div style="background: #e8f4fd; padding: 8px; margin: 5px 0; border-left: 4px solid #1976d2; font-family: monospace; font-size: 12px;">' . $output . '</div>';
    }
}

// ==============================================
// GLOBAL EXCEPTION HANDLING FOR AJAX REQUESTS
// ==============================================

/**
 * Handle AJAX errors and return JSON response
 * 
 * @param Exception $e Exception to handle
 */
function handleAjaxError($e) {
    if (isAjaxRequest()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => DEVELOPMENT_MODE ? $e->getMessage() : 'An error occurred',
            'code' => $e->getCode()
        ]);
        exit;
    }
    
    // Re-throw for normal page requests
    throw $e;
}

// ==============================================
// CLEANUP AND FINALIZATION
// ==============================================

/**
 * Application shutdown function
 */
function jhubShutdown() {
    // Capture any output
    $output = ob_get_clean();
    
    // Log any final errors
    if ($error = error_get_last()) {
        if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            logActivity('CRITICAL', "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    }
    
    // Output the content
    echo $output;
}

// Register shutdown function
register_shutdown_function('jhubShutdown');

// ==============================================
// INITIALIZATION COMPLETE
// ==============================================

// The application is now ready for use
// All configuration files have been loaded
// All core classes are available
// Database connection is established
// Session is initialized
// Security headers are set
// Logging is configured

/*
 * USAGE INSTRUCTIONS:
 * 
 * To use this initialization system in your PHP files:
 * 
 * <?php
 * require_once __DIR__ . '/includes/init.php';
 * 
 * // Your code here - all classes and functions are now available
 * $auth = new Auth();
 * $project = new Project();
 * // etc.
 * 
 * After including init.php, you have access to:
 * - All configuration constants and classes
 * - Database connection via Database::getInstance()
 * - Authentication system via new Auth()
 * - All helper functions
 * - Proper error handling
 * - Security headers
 * - Session management
 * 
 * The following constants are available to check initialization status:
 * - JHUB_ACCESS: Confirms proper access control
 * - JHUB_INITIALIZED: Confirms full initialization
 * - DEVELOPMENT_MODE: True if in development environment
 * - ROOT_DIR: Path to application root directory
 */