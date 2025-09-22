<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Authentication API Endpoint - Login
 * 
 * This endpoint handles login requests for all user types (admin, mentor, project)
 * and integrates with the main API router system.
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.',
        'allowed_methods' => ['POST']
    ]);
    exit;
}

/**
 * Authentication API Handler
 */
class AuthAPIHandler {
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /** @var Database Database instance */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auth = new Auth();
        $this->db = Database::getInstance();
    }
    
    /**
     * Process login request
     */
    public function handleLogin() {
        try {
            // Get request data
            $requestData = $this->getRequestData();
            
            // Validate required fields
            if (empty($requestData['login_type'])) {
                $this->sendResponse([
                    'success' => false,
                    'error' => 'Login type is required',
                    'code' => 'MISSING_LOGIN_TYPE'
                ], 400);
                return;
            }
            
            // Verify CSRF token for web requests (optional for API)
            if (isset($requestData['csrf_token']) && !$this->auth->verifyCSRFToken($requestData['csrf_token'])) {
                $this->sendResponse([
                    'success' => false,
                    'error' => 'Invalid security token',
                    'code' => 'INVALID_CSRF'
                ], 403);
                return;
            }
            
            // Rate limiting check
            $clientIP = getClientIP();
            $rateLimitKey = 'login_attempts_' . $clientIP;
            
            if (!$this->checkRateLimit($rateLimitKey, 10, 900)) { // 10 attempts per 15 minutes
                $this->sendResponse([
                    'success' => false,
                    'error' => 'Too many login attempts. Please try again later.',
                    'code' => 'RATE_LIMITED',
                    'retry_after' => 900
                ], 429);
                return;
            }
            
            // Process login based on type
            $loginType = $requestData['login_type'];
            $result = null;
            
            switch ($loginType) {
                case 'admin':
                    $result = $this->handleAdminLogin($requestData);
                    break;
                    
                case 'mentor':
                    $result = $this->handleMentorLogin($requestData);
                    break;
                    
                case 'project':
                    $result = $this->handleProjectLogin($requestData);
                    break;
                    
                default:
                    $this->sendResponse([
                        'success' => false,
                        'error' => 'Invalid login type',
                        'code' => 'INVALID_LOGIN_TYPE',
                        'valid_types' => ['admin', 'mentor', 'project']
                    ], 400);
                    return;
            }
            
            // Handle login result
            if ($result && $result['success']) {
                // Clear rate limiting on successful login
                $this->clearRateLimit($rateLimitKey);
                
                // Log successful login
                logActivity('INFO', 'API login successful', [
                    'login_type' => $loginType,
                    'user_id' => $result['user_id'] ?? null,
                    'ip_address' => $clientIP,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                // Add additional API response data
                $result['login_time'] = time();
                $result['expires_in'] = DatabaseConfig::SESSION_LIFETIME;
                $result['api_version'] = API_VERSION;
                
                $this->sendResponse($result, 200);
            } else {
                // Record failed login attempt
                $this->recordFailedAttempt($rateLimitKey);
                
                // Log failed login
                logActivity('WARNING', 'API login failed', [
                    'login_type' => $loginType,
                    'error' => $result['message'] ?? 'Unknown error',
                    'ip_address' => $clientIP,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                $this->sendResponse([
                    'success' => false,
                    'error' => $result['message'] ?? 'Login failed',
                    'code' => 'LOGIN_FAILED'
                ], 401);
            }
            
        } catch (Exception $e) {
            logActivity('ERROR', 'API login exception: ' . $e->getMessage(), [
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
     * Handle admin login
     * 
     * @param array $data Request data
     * @return array Login result
     */
    private function handleAdminLogin($data) {
        // Validate required fields
        if (empty($data['username']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Username and password are required',
                'required_fields' => ['username', 'password']
            ];
        }
        
        // Additional validation
        if (strlen(trim($data['username'])) < MIN_USERNAME_LENGTH) {
            return [
                'success' => false,
                'message' => 'Invalid username format'
            ];
        }
        
        if (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
            return [
                'success' => false,
                'message' => 'Invalid password format'
            ];
        }
        
        // Attempt login
        $result = $this->auth->loginAdmin($data['username'], $data['password']);
        
        // Add API-specific data
        if ($result && $result['success']) {
            $result['permissions'] = DatabaseConfig::getUserPermissions('admin');
            $result['dashboard_url'] = AppConfig::getDashboardUrl('admin');
            
            // Get admin statistics for dashboard
            if (class_exists('Project')) {
                $project = new Project();
                $result['dashboard_stats'] = $project->getSystemStats();
            }
        }
        
        return $result;
    }
    
    /**
     * Handle mentor login
     * 
     * @param array $data Request data
     * @return array Login result
     */
    private function handleMentorLogin($data) {
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email and password are required',
                'required_fields' => ['email', 'password']
            ];
        }
        
        // Additional validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address format'
            ];
        }
        
        if (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
            return [
                'success' => false,
                'message' => 'Invalid password format'
            ];
        }
        
        // Attempt login
        $result = $this->auth->loginMentor($data['email'], $data['password']);
        
        // Add API-specific data
        if ($result && $result['success']) {
            $result['permissions'] = DatabaseConfig::getUserPermissions('mentor');
            $result['dashboard_url'] = AppConfig::getDashboardUrl('mentor');
            
            // Get mentor-specific statistics
            if (class_exists('Project')) {
                $project = new Project();
                $mentorProjects = $project->getProjectsByMentor($result['user_id']);
                $availableProjects = $project->getAvailableProjectsForMentor($result['user_id']);
                
                $result['dashboard_stats'] = [
                    'my_projects_count' => count($mentorProjects['projects'] ?? []),
                    'available_projects_count' => count($availableProjects['projects'] ?? []),
                    'total_projects_mentored' => $this->getMentorTotalProjects($result['user_id'])
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Handle project login
     * 
     * @param array $data Request data
     * @return array Login result
     */
    private function handleProjectLogin($data) {
        // Validate required fields
        if (empty($data['profile_name']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Profile name and password are required',
                'required_fields' => ['profile_name', 'password']
            ];
        }
        
        // Additional validation
        if (strlen(trim($data['profile_name'])) < MIN_USERNAME_LENGTH) {
            return [
                'success' => false,
                'message' => 'Invalid profile name format'
            ];
        }
        
        if (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
            return [
                'success' => false,
                'message' => 'Invalid password format'
            ];
        }
        
        // Check for suspicious patterns (basic security)
        if (preg_match('/[\'";\\\\]/', $data['profile_name'])) {
            return [
                'success' => false,
                'message' => 'Invalid characters in profile name'
            ];
        }
        
        // Attempt login
        $result = $this->auth->loginProject($data['profile_name'], $data['password']);
        
        // Add API-specific data
        if ($result && $result['success']) {
            $result['permissions'] = DatabaseConfig::getUserPermissions('project');
            $result['dashboard_url'] = AppConfig::getDashboardUrl('project');
            
            // Get project-specific data for dashboard
            if (class_exists('Project')) {
                $project = new Project();
                $projectData = $project->getProject($result['project_id'], true, true);
                
                if ($projectData['success']) {
                    $result['project_details'] = $projectData['project'];
                    
                    // Get project timeline and recent activity
                    if (class_exists('Rating')) {
                        $rating = new Rating();
                        $timeline = $rating->getProjectTimeline($result['project_id']);
                        $result['project_timeline'] = $timeline['success'] ? $timeline['timeline'] : [];
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Get request data from JSON or form
     * 
     * @return array Request data
     */
    private function getRequestData() {
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
        
        // Remove sensitive data from response
        if (isset($response['password'])) {
            unset($response['password']);
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Check rate limiting
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @param int $timeWindow Time window in seconds
     * @return bool True if request allowed
     */
    private function checkRateLimit($key, $maxAttempts, $timeWindow) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        
        // Clean old attempts
        if (isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = array_filter(
                $_SESSION['rate_limits'][$key],
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        // Check if limit exceeded
        return count($_SESSION['rate_limits'][$key]) < $maxAttempts;
    }
    
    /**
     * Record failed attempt
     * 
     * @param string $key Rate limit key
     */
    private function recordFailedAttempt($key) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        $_SESSION['rate_limits'][$key][] = time();
    }
    
    /**
     * Clear rate limit
     * 
     * @param string $key Rate limit key
     */
    private function clearRateLimit($key) {
        if (isset($_SESSION['rate_limits'][$key])) {
            unset($_SESSION['rate_limits'][$key]);
        }
    }
    
    /**
     * Get total projects mentored by mentor
     * 
     * @param int $mentorId Mentor ID
     * @return int Total project count
     */
    private function getMentorTotalProjects($mentorId) {
        try {
            return $this->db->fetchColumn(
                "SELECT COUNT(DISTINCT project_id) FROM project_mentors WHERE mentor_id = ?",
                [$mentorId]
            );
        } catch (Exception $e) {
            return 0;
        }
    }
}

// ==============================================
// MAIN EXECUTION
// ==============================================

try {
    $handler = new AuthAPIHandler();
    $handler->handleLogin();
} catch (Throwable $e) {
    logActivity('CRITICAL', 'API login fatal error: ' . $e->getMessage(), [
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