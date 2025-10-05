<?php
/**
 * classes/Auth.php
 * Authentication System - FIXED VERSION
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
     */
    private function login($userType, $identifier, $password, $table, $idField, $identifierField) {
        try {
            // Check for brute force attacks
            if ($this->isAccountLocked($identifier, $userType)) {
                return ['success' => false, 'message' => 'Account temporarily locked due to too many failed attempts.'];
            }
            
            // Query to get user
            $sql = "SELECT * FROM {$table} WHERE {$identifierField} = ?";
            $user = $this->db->getRow($sql, [$identifier]);
            
            if (!$user) {
                $this->recordFailedAttempt($identifier, $userType);
                return ['success' => false, 'message' => 'Invalid credentials.'];
            }
            
            // Check if user is active
            $isActive = true;
            
            if (isset($user['is_active'])) {
                $isActive = (bool)$user['is_active'];
            } elseif (isset($user['status'])) {
                $isActive = ($user['status'] === 'active');
            }
            
            if (!$isActive) {
                return ['success' => false, 'message' => 'Account is inactive. Please contact support.'];
            }
            
            if (!isset($user['password']) || empty($user['password'])) {
                return ['success' => false, 'message' => 'Account configuration error.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($identifier, $userType);
                return ['success' => false, 'message' => 'Invalid credentials.'];
            }
            
            $this->clearLoginAttempts($identifier, $userType);
            
            // Create session
            $_SESSION['user_id'] = $user[$idField];
            $_SESSION['user_type'] = $userType;
            $_SESSION['user_identifier'] = $identifier;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['last_activity'] = time();
            
            // Update last login - FIXED
            $this->updateLastLogin($table, $idField, $user[$idField]);
            
            return [
                'success' => true,
                'message' => 'Login successful!',
                'user_id' => $user[$idField],
                'user_type' => $userType
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login.'];
        }
    }
    
    /**
     * Update last login - FIXED (No mixed parameters)
     */
    private function updateLastLogin($table, $idField, $userId) {
        $sql = "UPDATE {$table} SET last_login = ? WHERE {$idField} = ?";
        return $this->db->query($sql, [date('Y-m-d H:i:s'), $userId]);
    }
    
    /**
     * Check if user has valid session
     */
    public function isValidSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            return false;
        }
        
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if user is logged in (alias method)
     */
    public function isLoggedIn() {
        return $this->isValidSession();
    }
    
    /**
     * Get current user type
     */
    public function getUserType() {
        return $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user identifier
     */
    public function getUserIdentifier() {
        return $_SESSION['user_identifier'] ?? null;
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
                'manage_resources', 'create_assessments', 'create_learning_objectives'
            ],
            'project' => [
                'view_own_project', 'manage_team', 'add_innovators', 'comment_on_project'
            ]
        ];
        
        return isset($permissions[$userType]) && in_array($permission, $permissions[$userType]);
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
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            
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
            'admin' => SITE_URL . '/auth/admin-login.php',
            'mentor' => SITE_URL . '/auth/mentor-login.php',
            'project' => SITE_URL . '/auth/project-login.php'
        ];
        
        $userType = $this->getUserType();
        $loginUrl = $loginUrls[$userType] ?? SITE_URL . '/auth/admin-login.php';
        
        header("Location: {$loginUrl}");
        exit;
    }
    
    /**
     * Access denied page
     */
    public function accessDenied() {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title></head><body>";
        echo "<h1>Access Denied</h1><p>You don't have permission to access this resource.</p>";
        echo "</body></html>";
        exit;
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($identifier, $userType) {
        $key = 'login_attempts_' . $userType . '_' . md5($identifier);
        
        if (isset($_SESSION[$key . '_locked_until'])) {
            if (time() < $_SESSION[$key . '_locked_until']) {
                return true;
            } else {
                unset($_SESSION[$key . '_locked_until']);
                unset($_SESSION[$key]);
            }
        }
        
        return false;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($identifier, $userType) {
        $key = 'login_attempts_' . $userType . '_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
        }
        
        $_SESSION[$key]++;
        
        if ($_SESSION[$key] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$key . '_locked_until'] = time() + LOCKOUT_TIME;
        }
    }
    
    /**
     * Clear login attempts
     */
    private function clearLoginAttempts($identifier, $userType) {
        $key = 'login_attempts_' . $userType . '_' . md5($identifier);
        unset($_SESSION[$key]);
        unset($_SESSION[$key . '_locked_until']);
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>