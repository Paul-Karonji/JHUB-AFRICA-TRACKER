<?php
// classes/Auth.php
// Authentication System

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
    
    // Start secure session
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
    
    // Admin login
    public function loginAdmin($username, $password) {
        return $this->login('admin', $username, $password, 'admins', 'admin_id', 'username');
    }
    
    // Mentor login
    public function loginMentor($email, $password) {
        return $this->login('mentor', $email, $password, 'mentors', 'mentor_id', 'email');
    }
    
    // Project login
    public function loginProject($profile_name, $password) {
        return $this->login('project', $profile_name, $password, 'projects', 'project_id', 'profile_name');
    }
    
    // Generic login method
    private function login($userType, $identifier, $password, $table, $idField, $identifierField) {
        // Check for brute force attacks
        if ($this->isAccountLocked($identifier, $userType)) {
            return ['success' => false, 'message' => 'Account temporarily locked due to too many failed attempts.'];
        }
        
        $sql = "SELECT {$idField}, {$identifierField}, password FROM {$table} WHERE {$identifierField} = ? AND is_active = 1";
        $user = $this->db->getRow($sql, [$identifier]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $this->createSession($userType, $user[$idField], $user[$identifierField]);
            $this->updateLastLogin($table, $idField, $user[$idField]);
            $this->clearLoginAttempts($identifier, $userType);
            
            return ['success' => true, 'message' => MSG_SUCCESS_LOGIN];
        } else {
            // Failed login
            $this->recordFailedAttempt($identifier, $userType);
            return ['success' => false, 'message' => MSG_ERROR_LOGIN];
        }
    }
    
    // Create user session
    private function createSession($userType, $userId, $identifier) {
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_identifier'] = $identifier;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_type']) && isset($_SESSION['user_id']);
    }
    
    // Check if session is valid
    public function isValidSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
    
    // Get current user type
    public function getUserType() {
        return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
    }
    
    // Get current user ID
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    // Get current user identifier
    public function getUserIdentifier() {
        return isset($_SESSION['user_identifier']) ? $_SESSION['user_identifier'] : null;
    }
    
    // Check if user has specific permission
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
    
    // Require specific permission
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
    
    // Require specific user type
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
    
    // Generate CSRF token
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Validate CSRF token
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Logout user
    public function logout() {
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Redirect to appropriate login page
    public function redirectToLogin() {
        $loginUrls = [
            'admin' => '/auth/admin-login.php',
            'mentor' => '/auth/mentor-login.php',
            'project' => '/auth/project-login.php'
        ];
        
        $userType = $this->getUserType();
        $loginUrl = isset($loginUrls[$userType]) ? $loginUrls[$userType] : '/auth/login.php';
        
        header("Location: " . SITE_URL . $loginUrl);
    }
    
    // Access denied
    public function accessDenied() {
        http_response_code(403);
        echo "Access denied. You don't have permission to view this page.";
    }
    
    // Check if account is locked
    private function isAccountLocked($identifier, $userType) {
        $sql = "SELECT COUNT(*) as attempts FROM activity_logs 
                WHERE action = 'failed_login' 
                AND description LIKE ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $result = $this->db->getRow($sql, ["%{$userType}:{$identifier}%"]);
        
        return ($result && $result['attempts'] >= MAX_LOGIN_ATTEMPTS);
    }
    
    // Record failed login attempt
    private function recordFailedAttempt($identifier, $userType) {
        $this->db->insert('activity_logs', [
            'user_type' => 'system',
            'action' => 'failed_login',
            'description' => "Failed login attempt for {$userType}: {$identifier}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // Clear login attempts
    private function clearLoginAttempts($identifier, $userType) {
        // Login attempts are automatically cleared after 15 minutes
        // No action needed here
    }
    
    // Update last login time
    private function updateLastLogin($table, $idField, $userId) {
        $this->db->update($table, 
            ['last_login' => date('Y-m-d H:i:s')], 
            "{$idField} = ?", 
            [$userId]
        );
    }
    
    // Hash password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

?>