<?php
/**
 * classes/Auth.php
 * Authentication System - COMPLETE FIXED VERSION
 * 
 * Replace your entire Auth.php file with this code
 */

class Auth {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }
    
    /**
     * Start secure session
     */
    public function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Admin login
     */
    public function loginAdmin($username, $password) {
        return $this->login('admin', $username, $password, 'admins', 'admin_id', 'username');
    }
    
    /**
     * Mentor login
     */
    public function loginMentor($email, $password) {
        return $this->login('mentor', $email, $password, 'mentors', 'mentor_id', 'email');
    }
    
    /**
     * Project login
     */
    public function loginProject($profile_name, $password) {
        return $this->login('project', $profile_name, $password, 'projects', 'project_id', 'profile_name');
    }
    
    /**
     * Generic login method - FIXED VERSION
     * Handles both 'is_active' (admins/mentors) and 'status' (projects) fields
     */
    private function login($userType, $identifier, $password, $table, $idField, $identifierField) {
        try {
            // Check for brute force attacks
            if ($this->isAccountLocked($identifier, $userType)) {
                return ['success' => false, 'message' => 'Account temporarily locked due to too many failed attempts.'];
            }
            
            // Query to get user - SELECT ALL FIELDS
            $sql = "SELECT * FROM {$table} WHERE {$identifierField} = ?";
            $user = $this->db->getRow($sql, [$identifier]);
            
            if (!$user) {
                $this->recordFailedAttempt($identifier, $userType);
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            
            // Check if user is active - handle BOTH 'is_active' AND 'status' fields
            $isActive = true; // Default to true
            
            if (isset($user['is_active'])) {
                // Admins and Mentors use 'is_active' field (TINYINT: 0 or 1)
                $isActive = (bool)$user['is_active'];
            } elseif (isset($user['status'])) {
                // Projects use 'status' field (VARCHAR: 'active', 'inactive', etc.)
                $isActive = ($user['status'] === 'active');
            }
            
            if (!$isActive) {
                return ['success' => false, 'message' => 'Account is inactive. Please contact support.'];
            }
            
            // Verify password exists
            if (!isset($user['password']) || empty($user['password'])) {
                return ['success' => false, 'message' => 'Account configuration error. Please contact support.'];
            }
            
            // Verify password matches
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($identifier, $userType);
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            
            // Clear any failed attempts
            $this->clearLoginAttempts($identifier, $userType);
            
            // Create session
            $_SESSION['user_id'] = $user[$idField];
            $_SESSION['user_type'] = $userType;
            $_SESSION['user_identifier'] = $identifier;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['last_activity'] = time();
            
            // Update last login time
            $this->updateLastLogin($table, $idField, $user[$idField]);
            
            return [
                'success' => true,
                'message' => 'Login successful!',
                'user_id' => $user[$idField],
                'user_type' => $userType
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login. Please try again.'];
        }
    }
    
    /**
     * Check if user has valid session
     */
    public function isValidSession() {
        // Check if session has required data
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            return false;
        }
        
        // Check for session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get current user type
     */
    public function getUserType() {
        return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current user identifier
     */
    public function getUserIdentifier() {
        return isset($_SESSION['user_identifier']) ? $_SESSION['user_identifier'] : null;
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission) {
        $userType = $this->getUserType();
        
        $permissions = [
            'admin' => [
                'view_all_projects', 'create_project_direct', 'terminate_project',
                'manage_mentors', 'manage_admins', 'remove_innovators',
                'approve_applications', 'view_reports'
            ],
            'mentor' => [
                'view_all_projects', 'assign_to_project', 'rate_project',
                'manage_resources', 'create_assessments', 'create_learning_objectives',
                'remove_innovators_from_assigned_projects'
            ],
            'project' => [
                'view_own_project', 'manage_team', 'add_innovators', 'comment_on_project'
            ]
        ];
        
        return isset($permissions[$userType]) && in_array($permission, $permissions[$userType]);
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission($permission) {
        if (!$this->isValidSession()) {
            $this->redirectToLogin();
            exit;
        }
        
        if (!$this->hasPermission($permission)) {
            $this->accessDenied();
            exit;
        }
    }
    
    /**
     * Require specific user type
     */
    public function requireUserType($userType) {
        if (!$this->isValidSession()) {
            $this->redirectToLogin();
            exit;
        }
        
        if ($this->getUserType() !== $userType) {
            $this->accessDenied();
            exit;
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            
            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
        }
    }
    
    /**
     * Redirect to appropriate login page
     */
    public function redirectToLogin() {
        $loginUrls = [
            'admin' => '/auth/admin-login.php',
            'mentor' => '/auth/mentor-login.php',
            'project' => '/auth/project-login.php'
        ];
        
        $userType = $this->getUserType();
        $loginUrl = isset($loginUrls[$userType]) ? $loginUrls[$userType] : '/auth/project-login.php';
        
        header("Location: " . SITE_URL . $loginUrl);
        exit;
    }
    
    /**
     * Access denied
     */
    public function accessDenied() {
        http_response_code(403);
        echo "Access denied. You don't have permission to view this page.";
        exit;
    }
    
    /**
     * Check if account is locked due to failed login attempts
     */
    private function isAccountLocked($identifier, $userType) {
        try {
            $sql = "SELECT COUNT(*) as attempts FROM activity_logs 
                    WHERE action = 'failed_login' 
                    AND description LIKE ? 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            
            $result = $this->db->getRow($sql, ["%{$userType}%{$identifier}%"]);
            
            if (!$result) {
                return false;
            }
            
            return ($result['attempts'] >= MAX_LOGIN_ATTEMPTS);
            
        } catch (Exception $e) {
            // If activity_logs table doesn't exist or there's an error, don't lock account
            error_log("Account lock check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($identifier, $userType) {
        try {
            $this->db->insert('activity_logs', [
                'user_type' => 'system',
                'action' => 'failed_login',
                'description' => "Failed login attempt for {$userType}: {$identifier}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Silently fail if we can't log - don't break login flow
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Clear login attempts
     */
    private function clearLoginAttempts($identifier, $userType) {
        // Login attempts are automatically cleared after 15 minutes
        // No action needed here
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($table, $idField, $userId) {
        try {
            $this->db->update(
                $table, 
                ['last_login' => date('Y-m-d H:i:s')], 
                "{$idField} = ?", 
                [$userId]
            );
        } catch (Exception $e) {
            // Silently fail if we can't update - don't break login flow
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>