<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Authentication Management Class
 * 
 * This class handles all authentication operations including login, logout,
 * session management, and user verification for all user types.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Prevent direct access
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Authentication Class
 * 
 * Manages multi-type user authentication and session handling
 */
class Auth {
    
    /** @var Database Database instance */
    private $db;
    
    /** @var array Login attempt tracking */
    private $loginAttempts = [];
    
    /** @var string Current user type */
    private $currentUserType = null;
    
    /** @var int Current user ID */
    private $currentUserId = null;
    
    /**
     * Constructor - Initialize authentication system
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeSession();
        $this->loadCurrentUser();
    }
    
    /**
     * Initialize session if not already started
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            AppConfig::initializeSession();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif ($_SESSION['last_regeneration'] < (time() - 300)) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Load current user information from session
     */
    private function loadCurrentUser() {
        if (isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
            $this->currentUserType = $_SESSION['user_type'];
            $this->currentUserId = $_SESSION['user_id'];
        }
    }
    
    /**
     * Admin login authentication
     * 
     * @param string $username Admin username
     * @param string $password Admin password
     * @return array Login result
     */
    public function loginAdmin($username, $password) {
        try {
            // Check login attempts
            if ($this->isLoginBlocked('admin', $username)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again later.',
                    'blocked_until' => $this->getBlockedUntil('admin', $username)
                ];
            }
            
            // Validate input
            if (empty($username) || empty($password)) {
                $this->recordFailedLogin('admin', $username);
                return ['success' => false, 'message' => 'Username and password are required'];
            }
            
            // Get admin from database
            $stmt = $this->db->prepare("SELECT id, username, password FROM admins WHERE username = ?");
            $this->db->execute($stmt, [$username]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                $this->recordFailedLogin('admin', $username);
                return ['success' => false, 'message' => 'Invalid admin credentials'];
            }
            
            // Login successful - create session
            $this->createUserSession('admin', $admin['id'], [
                'username' => $admin['username']
            ]);
            
            $this->clearFailedLogins('admin', $username);
            
            logActivity('INFO', "Admin login successful", ['username' => $username]);
            
            return [
                'success' => true,
                'user_type' => 'admin',
                'user_id' => $admin['id'],
                'username' => $admin['username'],
                'redirect' => AppConfig::getDashboardUrl('admin')
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Admin login error: " . $e->getMessage(), ['username' => $username]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Mentor login authentication
     * 
     * @param string $email Mentor email
     * @param string $password Mentor password
     * @return array Login result
     */
    public function loginMentor($email, $password) {
        try {
            // Check login attempts
            if ($this->isLoginBlocked('mentor', $email)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again later.',
                    'blocked_until' => $this->getBlockedUntil('mentor', $email)
                ];
            }
            
            // Validate input
            if (empty($email) || empty($password)) {
                $this->recordFailedLogin('mentor', $email);
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Get mentor from database
            $stmt = $this->db->prepare("
                SELECT id, name, email, password, bio, expertise 
                FROM mentors 
                WHERE email = ?
            ");
            $this->db->execute($stmt, [$email]);
            $mentor = $stmt->fetch();
            
            if (!$mentor || !password_verify($password, $mentor['password'])) {
                $this->recordFailedLogin('mentor', $email);
                return ['success' => false, 'message' => 'Invalid mentor credentials'];
            }
            
            // Login successful - create session
            $this->createUserSession('mentor', $mentor['id'], [
                'name' => $mentor['name'],
                'email' => $mentor['email'],
                'expertise' => $mentor['expertise'],
                'bio' => $mentor['bio']
            ]);
            
            $this->clearFailedLogins('mentor', $email);
            
            logActivity('INFO', "Mentor login successful", ['email' => $email, 'name' => $mentor['name']]);
            
            return [
                'success' => true,
                'user_type' => 'mentor',
                'user_id' => $mentor['id'],
                'name' => $mentor['name'],
                'email' => $mentor['email'],
                'expertise' => $mentor['expertise'],
                'redirect' => AppConfig::getDashboardUrl('mentor')
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Mentor login error: " . $e->getMessage(), ['email' => $email]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Project login authentication
     * 
     * @param string $profileName Project profile name
     * @param string $password Project password
     * @return array Login result
     */
    public function loginProject($profileName, $password) {
        try {
            // Check login attempts
            if ($this->isLoginBlocked('project', $profileName)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again later.',
                    'blocked_until' => $this->getBlockedUntil('project', $profileName)
                ];
            }
            
            // Validate input
            if (empty($profileName) || empty($password)) {
                $this->recordFailedLogin('project', $profileName);
                return ['success' => false, 'message' => 'Profile name and password are required'];
            }
            
            // Get project from database with additional info
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.profile_name, p.password, p.status, p.description,
                       p.email, p.website,
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       COUNT(pi.id) as innovator_count
                FROM projects p
                LEFT JOIN project_innovators pi ON p.id = pi.project_id
                LEFT JOIN (
                    SELECT project_id, stage, percentage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE p.profile_name = ? AND p.status != 'terminated'
                GROUP BY p.id
            ");
            $this->db->execute($stmt, [$profileName]);
            $project = $stmt->fetch();
            
            if (!$project || !password_verify($password, $project['password'])) {
                $this->recordFailedLogin('project', $profileName);
                return ['success' => false, 'message' => 'Invalid project credentials'];
            }
            
            if ($project['status'] === 'terminated') {
                return ['success' => false, 'message' => 'This project has been terminated'];
            }
            
            // Login successful - create session
            $this->createUserSession('project', $project['id'], [
                'project_name' => $project['name'],
                'profile_name' => $project['profile_name'],
                'project_status' => $project['status'],
                'current_stage' => $project['current_stage'],
                'current_percentage' => $project['current_percentage'],
                'innovator_count' => $project['innovator_count'],
                'description' => $project['description'],
                'email' => $project['email'],
                'website' => $project['website']
            ]);
            
            $this->clearFailedLogins('project', $profileName);
            
            logActivity('INFO', "Project login successful", [
                'profile_name' => $profileName, 
                'project_name' => $project['name']
            ]);
            
            return [
                'success' => true,
                'user_type' => 'project',
                'project_id' => $project['id'],
                'project_name' => $project['name'],
                'profile_name' => $project['profile_name'],
                'current_stage' => $project['current_stage'],
                'status' => $project['status'],
                'redirect' => AppConfig::getDashboardUrl('project')
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Project login error: " . $e->getMessage(), ['profile_name' => $profileName]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Create user session
     * 
     * @param string $userType User type
     * @param int $userId User ID
     * @param array $additionalData Additional session data
     */
    private function createUserSession($userType, $userId, $additionalData = []) {
        // Clear existing session data
        session_unset();
        
        // Set base session data
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Add additional data
        foreach ($additionalData as $key => $value) {
            $_SESSION[$key] = $value;
        }
        
        // Set current user properties
        $this->currentUserType = $userType;
        $this->currentUserId = $userId;
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Logout user and destroy session
     * 
     * @return array Logout result
     */
    public function logout() {
        try {
            $userType = $this->currentUserType;
            $userId = $this->currentUserId;
            
            // Log the logout
            if ($userType && $userId) {
                logActivity('INFO', "User logout", [
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
            }
            
            // Clear session data
            session_unset();
            session_destroy();
            
            // Clear instance properties
            $this->currentUserType = null;
            $this->currentUserId = null;
            
            return [
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => AppConfig::getUrl()
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Logout error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Logout failed'];
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_type']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Check session timeout
        if ((time() - $_SESSION['login_time']) > DatabaseConfig::SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        
        // Check last activity timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get current user type
     * 
     * @return string|null User type or null if not authenticated
     */
    public function getUserType() {
        return $this->isAuthenticated() ? $this->currentUserType : null;
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    public function getUserId() {
        return $this->isAuthenticated() ? $this->currentUserId : null;
    }
    
    /**
     * Get current project ID (for project users)
     * 
     * @return int|null Project ID or null if not project user
     */
    public function getProjectId() {
        if ($this->getUserType() === 'project') {
            return $this->getUserId();
        }
        return null;
    }
    
    /**
     * Get session data
     * 
     * @param string $key Session key
     * @param mixed $default Default value
     * @return mixed Session value
     */
    public function getSessionData($key, $default = null) {
        return $this->isAuthenticated() ? ($_SESSION[$key] ?? $default) : $default;
    }
    
    /**
     * Set session data
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     * @return bool True if set successfully
     */
    public function setSessionData($key, $value) {
        if ($this->isAuthenticated()) {
            $_SESSION[$key] = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Check if user has specific permission
     * 
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    public function hasPermission($permission) {
        $userType = $this->getUserType();
        if (!$userType) {
            return false;
        }
        
        return DatabaseConfig::userHasPermission($userType, $permission);
    }
    
    /**
     * Require specific user type (throw exception if not authorized)
     * 
     * @param string $requiredType Required user type
     * @throws Exception If user is not authorized
     */
    public function requireUserType($requiredType) {
        if (!$this->isAuthenticated()) {
            throw new Exception("Authentication required", 401);
        }
        
        if ($this->getUserType() !== $requiredType) {
            throw new Exception("Access denied. Required role: $requiredType", 403);
        }
    }
    
    /**
     * Require minimum user role level
     * 
     * @param string $minimumType Minimum required user type
     * @throws Exception If user doesn't have sufficient role
     */
    public function requireMinimumRole($minimumType) {
        if (!$this->isAuthenticated()) {
            throw new Exception("Authentication required", 401);
        }
        
        if (!hasRequiredRole($this->getUserType(), $minimumType)) {
            throw new Exception("Insufficient permissions", 403);
        }
    }
    
    /**
     * Hash password using secure algorithm
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => DatabaseConfig::PASSWORD_HASH_COST
        ]);
    }
    
    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Record failed login attempt
     * 
     * @param string $userType User type
     * @param string $identifier User identifier (username/email/profile_name)
     */
    private function recordFailedLogin($userType, $identifier) {
        $key = $userType . ':' . $identifier;
        $ip = getClientIP();
        
        if (!isset($this->loginAttempts[$key])) {
            $this->loginAttempts[$key] = [];
        }
        
        $this->loginAttempts[$key][] = [
            'time' => time(),
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Store in session for persistence
        $_SESSION['login_attempts'] = $this->loginAttempts;
        
        logActivity('WARNING', "Failed login attempt", [
            'user_type' => $userType,
            'identifier' => $identifier,
            'ip' => $ip
        ]);
    }
    
    /**
     * Check if login is blocked due to too many failed attempts
     * 
     * @param string $userType User type
     * @param string $identifier User identifier
     * @return bool True if blocked
     */
    private function isLoginBlocked($userType, $identifier) {
        $key = $userType . ':' . $identifier;
        
        // Load attempts from session
        if (isset($_SESSION['login_attempts'])) {
            $this->loginAttempts = $_SESSION['login_attempts'];
        }
        
        if (!isset($this->loginAttempts[$key])) {
            return false;
        }
        
        $attempts = $this->loginAttempts[$key];
        $recentAttempts = array_filter($attempts, function($attempt) {
            return (time() - $attempt['time']) < DatabaseConfig::LOGIN_LOCKOUT_TIME;
        });
        
        return count($recentAttempts) >= DatabaseConfig::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Get timestamp when login block expires
     * 
     * @param string $userType User type
     * @param string $identifier User identifier
     * @return int|null Block expiry timestamp
     */
    private function getBlockedUntil($userType, $identifier) {
        $key = $userType . ':' . $identifier;
        
        if (!isset($this->loginAttempts[$key])) {
            return null;
        }
        
        $attempts = $this->loginAttempts[$key];
        if (empty($attempts)) {
            return null;
        }
        
        $lastAttempt = max(array_column($attempts, 'time'));
        return $lastAttempt + DatabaseConfig::LOGIN_LOCKOUT_TIME;
    }
    
    /**
     * Clear failed login attempts
     * 
     * @param string $userType User type
     * @param string $identifier User identifier
     */
    private function clearFailedLogins($userType, $identifier) {
        $key = $userType . ':' . $identifier;
        unset($this->loginAttempts[$key]);
        
        if (isset($_SESSION['login_attempts'])) {
            unset($_SESSION['login_attempts'][$key]);
        }
    }
    
    /**
     * Get user information array
     * 
     * @return array|null User information or null if not authenticated
     */
    public function getUserInfo() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userType = $this->getUserType();
        $userId = $this->getUserId();
        
        $info = [
            'user_type' => $userType,
            'user_id' => $userId,
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity']
        ];
        
        switch ($userType) {
            case 'admin':
                $info['username'] = $_SESSION['username'] ?? null;
                break;
                
            case 'mentor':
                $info['name'] = $_SESSION['name'] ?? null;
                $info['email'] = $_SESSION['email'] ?? null;
                $info['expertise'] = $_SESSION['expertise'] ?? null;
                $info['bio'] = $_SESSION['bio'] ?? null;
                break;
                
            case 'project':
                $info['project_name'] = $_SESSION['project_name'] ?? null;
                $info['profile_name'] = $_SESSION['profile_name'] ?? null;
                $info['project_status'] = $_SESSION['project_status'] ?? null;
                $info['current_stage'] = $_SESSION['current_stage'] ?? null;
                $info['current_percentage'] = $_SESSION['current_percentage'] ?? null;
                $info['innovator_count'] = $_SESSION['innovator_count'] ?? null;
                break;
        }
        
        return $info;
    }
    
    /**
     * Update user session data (useful for keeping data fresh)
     * 
     * @return bool True if updated successfully
     */
    public function refreshUserSession() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        try {
            $userType = $this->getUserType();
            $userId = $this->getUserId();
            
            switch ($userType) {
                case 'admin':
                    $stmt = $this->db->prepare("SELECT username FROM admins WHERE id = ?");
                    $this->db->execute($stmt, [$userId]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $_SESSION['username'] = $user['username'];
                    }
                    break;
                    
                case 'mentor':
                    $stmt = $this->db->prepare("SELECT name, email, bio, expertise FROM mentors WHERE id = ?");
                    $this->db->execute($stmt, [$userId]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['bio'] = $user['bio'];
                        $_SESSION['expertise'] = $user['expertise'];
                    }
                    break;
                    
                case 'project':
                    $stmt = $this->db->prepare("
                        SELECT p.name, p.status, p.description, p.email, p.website,
                               COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                               COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                               COUNT(pi.id) as innovator_count
                        FROM projects p
                        LEFT JOIN project_innovators pi ON p.id = pi.project_id
                        LEFT JOIN (
                            SELECT project_id, stage, percentage,
                                   ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                            FROM ratings
                        ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                        WHERE p.id = ?
                        GROUP BY p.id
                    ");
                    $this->db->execute($stmt, [$userId]);
                    $project = $stmt->fetch();
                    if ($project) {
                        $_SESSION['project_name'] = $project['name'];
                        $_SESSION['project_status'] = $project['status'];
                        $_SESSION['current_stage'] = $project['current_stage'];
                        $_SESSION['current_percentage'] = $project['current_percentage'];
                        $_SESSION['innovator_count'] = $project['innovator_count'];
                        $_SESSION['description'] = $project['description'];
                        $_SESSION['email'] = $project['email'];
                        $_SESSION['website'] = $project['website'];
                    }
                    break;
            }
            
            return true;
            
        } catch (Exception $e) {
            logActivity('ERROR', "Session refresh error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate secure CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if token is valid
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check if current session is valid and secure
     * 
     * @return bool True if session is valid
     */
    public function validateSession() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Check IP address consistency (optional, can be disabled for mobile users)
        if (AppConfig::isFeatureEnabled('strict_ip_checking')) {
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== getClientIP()) {
                logActivity('WARNING', "Session IP mismatch detected");
                $this->logout();
                return false;
            }
        }
        
        // Check user agent consistency
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            logActivity('WARNING', "Session user agent mismatch detected");
            $this->logout();
            return false;
        }
        
        return true;
    }
}