<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Authentication API Endpoint - Logout
 * 
 * This endpoint handles logout requests for all user types and
 * properly cleans up sessions and security tokens.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/../../includes/init.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow both POST and GET for logout (POST preferred)
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST or GET.',
        'allowed_methods' => ['POST', 'GET']
    ]);
    exit;
}

/**
 * Logout API Handler
 */
class LogoutAPIHandler {
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auth = new Auth();
    }
    
    /**
     * Process logout request
     */
    public function handleLogout() {
        try {
            // Get request data
            $requestData = $this->getRequestData();
            
            // Check if user is currently logged in
            if (!$this->auth->isAuthenticated()) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Already logged out',
                    'was_authenticated' => false
                ], 200);
                return;
            }
            
            // Get user info before logout (for logging)
            $userInfo = $this->auth->getUserInfo();
            
            // Verify CSRF token for POST requests (optional for GET)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
                isset($requestData['csrf_token']) && 
                !$this->auth->verifyCSRFToken($requestData['csrf_token'])) {
                
                $this->sendResponse([
                    'success' => false,
                    'error' => 'Invalid security token',
                    'code' => 'INVALID_CSRF'
                ], 403);
                return;
            }
            
            // Perform logout
            $result = $this->auth->logout();
            
            if ($result['success']) {
                // Log successful logout
                logActivity('INFO', 'API logout successful', [
                    'user_type' => $userInfo['user_type'] ?? 'unknown',
                    'user_id' => $userInfo['user_id'] ?? null,
                    'logout_method' => $_SERVER['REQUEST_METHOD'],
                    'ip_address' => getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                // Add API-specific data
                $response = [
                    'success' => true,
                    'message' => 'Logged out successfully',
                    'was_authenticated' => true,
                    'user_type' => $userInfo['user_type'] ?? null,
                    'logout_time' => time(),
                    'redirect_url' => $this->getRedirectUrl($requestData)
                ];
                
                $this->sendResponse($response, 200);
            } else {
                // Logout failed (shouldn't normally happen)
                logActivity('WARNING', 'API logout failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'user_type' => $userInfo['user_type'] ?? 'unknown',
                    'ip_address' => getClientIP()
                ]);
                
                $this->sendResponse([
                    'success' => false,
                    'error' => $result['message'] ?? 'Logout failed',
                    'code' => 'LOGOUT_FAILED'
                ], 500);
            }
            
        } catch (Exception $e) {
            logActivity('ERROR', 'API logout exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendResponse([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'SERVER_ERROR'
            ], 500);
        }
    }
    
    /**
     * Get redirect URL based on user preferences or default
     * 
     * @param array $requestData Request data
     * @return string Redirect URL
     */
    private function getRedirectUrl($requestData) {
        // Check if specific redirect URL was requested
        if (!empty($requestData['redirect_url'])) {
            $requestedUrl = $requestData['redirect_url'];
            
            // Validate redirect URL for security (prevent open redirects)
            if ($this->isValidRedirectUrl($requestedUrl)) {
                return $requestedUrl;
            }
        }
        
        // Default redirect to login page
        return AppConfig::getUrl('auth/login.php');
    }
    
    /**
     * Validate redirect URL to prevent open redirect attacks
     * 
     * @param string $url URL to validate
     * @return bool True if URL is safe to redirect to
     */
    private function isValidRedirectUrl($url) {
        // Only allow relative URLs or URLs to the same domain
        if (strpos($url, '/') === 0) {
            // Relative URL - safe
            return true;
        }
        
        // Check if URL is to the same domain
        $parsedUrl = parse_url($url);
        $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
        
        if (isset($parsedUrl['host']) && $parsedUrl['host'] === $currentDomain) {
            return true;
        }
        
        // Check against allowed domains (if configured)
        $allowedDomains = [
            parse_url(AppConfig::APP_URL, PHP_URL_HOST),
            $currentDomain
        ];
        
        return isset($parsedUrl['host']) && in_array($parsedUrl['host'], $allowedDomains);
    }
    
    /**
     * Get request data from JSON or form
     * 
     * @return array Request data
     */
    private function getRequestData() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $_GET;
        }
        
        // Try to get JSON data first
        $input = file_get_contents('php://input');
        
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        // Fallback to POST data
        return $_POST;
    }
    
    /**
     * Send JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        
        // Add common response metadata
        $response = array_merge($data, [
            'timestamp' => time(),
            'api_version' => API_VERSION
        ]);
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ==============================================
// MAIN EXECUTION
// ==============================================

try {
    $handler = new LogoutAPIHandler();
    $handler->handleLogout();
} catch (Throwable $e) {
    logActivity('CRITICAL', 'API logout fatal error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'FATAL_ERROR',
        'timestamp' => time(),
        'api_version' => API_VERSION
    ]);
}