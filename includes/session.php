<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Session Management Helper Functions
 * 
 * This file provides helper functions for session management,
 * user authentication checks, and session security.
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
 * Check if user is logged in
 * 
 * @return bool True if user is authenticated
 */
function isLoggedIn() {
    return isset($_SESSION['user_type']) && 
           isset($_SESSION['user_id']) && 
           isset($_SESSION['login_time']) &&
           (time() - $_SESSION['login_time']) < DatabaseConfig::SESSION_LIFETIME;
}

/**
 * Get current user type
 * 
 * @return string|null User type or null if not logged in
 */
function getCurrentUserType() {
    return isLoggedIn() ? $_SESSION['user_type'] : null;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Check if current user has specific user type
 * 
 * @param string $requiredType Required user type
 * @return bool True if user has required type
 */
function hasUserType($requiredType) {
    return getCurrentUserType() === $requiredType;
}

/**
 * Check if current user has minimum role level
 * 
 * @param string $minimumType Minimum required user type
 * @return bool True if user has sufficient role
 */
function hasMinimumRole($minimumType) {
    $currentType = getCurrentUserType();
    if (!$currentType) {
        return false;
    }
    
    return hasRequiredRole($currentType, $minimumType);
}

/**
 * Require user to be logged in (redirect if not)
 * 
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $redirectUrl = $redirectUrl ?: AppConfig::getLoginUrl();
        redirect($redirectUrl);
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}

/**
 * Require specific user type (show error if not authorized)
 * 
 * @param string $requiredType Required user type
 * @param bool $redirect Whether to redirect or show error page
 */
function requireUserType($requiredType, $redirect = true) {
    requireLogin();
    
    if (!hasUserType($requiredType)) {
        if ($redirect) {
            // Redirect to appropriate login page
            redirect(AppConfig::getLoginUrl($requiredType) . '?error=access_denied');
        } else {
            // Show access denied page
            http_response_code(403);
            include ROOT_DIR . 'templates/access-denied.php';
            exit;
        }
    }
}

/**
 * Require minimum user role
 * 
 * @param string $minimumType Minimum required user type
 * @param bool $redirect Whether to redirect or show error page
 */
function requireMinimumRole($minimumType, $redirect = true) {
    requireLogin();
    
    if (!hasMinimumRole($minimumType)) {
        if ($redirect) {
            redirect(AppConfig::getLoginUrl() . '?error=insufficient_permissions');
        } else {
            http_response_code(403);
            include ROOT_DIR . 'templates/access-denied.php';
            exit;
        }
    }
}

/**
 * Get user session data
 * 
 * @param string $key Session key
 * @param mixed $default Default value if key not found
 * @return mixed Session value
 */
function getUserSession($key, $default = null) {
    return isLoggedIn() ? ($_SESSION[$key] ?? $default) : $default;
}

/**
 * Set user session data
 * 
 * @param string $key Session key
 * @param mixed $value Session value
 * @return bool True if set successfully
 */
function setUserSession($key, $value) {
    if (isLoggedIn()) {
        $_SESSION[$key] = $value;
        return true;
    }
    return false;
}

/**
 * Get current user info array
 * 
 * @return array|null User information or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userType = getCurrentUserType();
    $userId = getCurrentUserId();
    
    $user = [
        'user_type' => $userType,
        'user_id' => $userId,
        'login_time' => $_SESSION['login_time'],
        'last_activity' => $_SESSION['last_activity'] ?? $_SESSION['login_time']
    ];
    
    // Add type-specific data
    switch ($userType) {
        case 'admin':
            $user['username'] = $_SESSION['username'] ?? null;
            break;
            
        case 'mentor':
            $user['name'] = $_SESSION['name'] ?? null;
            $user['email'] = $_SESSION['email'] ?? null;
            $user['expertise'] = $_SESSION['expertise'] ?? null;
            $user['bio'] = $_SESSION['bio'] ?? null;
            break;
            
        case 'project':
            $user['project_id'] = $userId;
            $user['project_name'] = $_SESSION['project_name'] ?? null;
            $user['profile_name'] = $_SESSION['profile_name'] ?? null;
            $user['project_status'] = $_SESSION['project_status'] ?? null;
            $user['current_stage'] = $_SESSION['current_stage'] ?? null;
            $user['current_percentage'] = $_SESSION['current_percentage'] ?? null;
            break;
    }
    
    return $user;
}

/**
 * Check if user has specific permission
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function hasPermission($permission) {
    $userType = getCurrentUserType();
    if (!$userType) {
        return false;
    }
    
    return DatabaseConfig::userHasPermission($userType, $permission);
}

/**
 * Generate and get CSRF token
 * 
 * @return string CSRF token
 */
function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token HTML input field
 * 
 * @return string HTML input field
 */
function csrfTokenField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate current session security
 * 
 * @return bool True if session is secure and valid
 */
function validateSessionSecurity() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check session timeout
    if ((time() - $_SESSION['login_time']) > DatabaseConfig::SESSION_LIFETIME) {
        destroyUserSession();
        return false;
    }
    
    // Check activity timeout (30 minutes)
    $lastActivity = $_SESSION['last_activity'] ?? $_SESSION['login_time'];
    if ((time() - $lastActivity) > 1800) {
        destroyUserSession();
        return false;
    }
    
    // Check IP consistency (if enabled)
    if (AppConfig::isFeatureEnabled('strict_ip_checking')) {
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== getClientIP()) {
            logActivity('WARNING', 'Session IP mismatch detected', [
                'session_ip' => $_SESSION['ip_address'],
                'current_ip' => getClientIP(),
                'user_type' => getCurrentUserType(),
                'user_id' => getCurrentUserId()
            ]);
            destroyUserSession();
            return false;
        }
    }
    
    return true;
}

/**
 * Update session activity timestamp
 */
function updateSessionActivity() {
    if (isLoggedIn()) {
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            ($_SESSION['last_regeneration'] < (time() - 300))) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Destroy user session
 */
function destroyUserSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Log logout
        if (isLoggedIn()) {
            logActivity('INFO', 'Session destroyed', [
                'user_type' => getCurrentUserType(),
                'user_id' => getCurrentUserId()
            ]);
        }
        
        // Clear session data
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
    }
}

/**
 * Get session time remaining in seconds
 * 
 * @return int|null Seconds remaining or null if not logged in
 */
function getSessionTimeRemaining() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $loginTime = $_SESSION['login_time'];
    $maxLifetime = DatabaseConfig::SESSION_LIFETIME;
    $elapsed = time() - $loginTime;
    
    return max(0, $maxLifetime - $elapsed);
}

/**
 * Check if session is about to expire (less than 5 minutes remaining)
 * 
 * @return bool True if session expires soon
 */
function isSessionExpiringSoon() {
    $remaining = getSessionTimeRemaining();
    return $remaining !== null && $remaining < 300; // 5 minutes
}

/**
 * Get human-readable session time remaining
 * 
 * @return string|null Time remaining string or null if not logged in
 */
function getSessionTimeRemainingFormatted() {
    $seconds = getSessionTimeRemaining();
    if ($seconds === null) {
        return null;
    }
    
    if ($seconds < 60) {
        return $seconds . ' seconds';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        $formatted = $hours . ' hour' . ($hours !== 1 ? 's' : '');
        if ($minutes > 0) {
            $formatted .= ', ' . $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
        }
        return $formatted;
    }
}

/**
 * Set flash message for next request
 * 
 * @param string $message Message content
 * @param string $type Message type (success, error, warning, info)
 * @param string $key Message key (for multiple messages)
 */
function setFlashMessage($message, $type = 'info', $key = 'default') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][$key] = [
        'message' => $message,
        'type' => $type,
        'time' => time()
    ];
}

/**
 * Get and clear flash messages
 * 
 * @param string|null $key Specific message key or null for all
 * @return array Flash messages
 */
function getFlashMessages($key = null) {
    $messages = $_SESSION['flash_messages'] ?? [];
    
    if ($key !== null) {
        $message = $messages[$key] ?? null;
        unset($_SESSION['flash_messages'][$key]);
        return $message ? [$key => $message] : [];
    }
    
    // Return all messages and clear them
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if there are flash messages
 * 
 * @param string|null $key Specific message key or null for any
 * @return bool True if messages exist
 */
function hasFlashMessages($key = null) {
    $messages = $_SESSION['flash_messages'] ?? [];
    
    if ($key !== null) {
        return isset($messages[$key]);
    }
    
    return !empty($messages);
}

/**
 * Display flash messages HTML
 * 
 * @param string|null $key Specific message key or null for all
 * @return string HTML for flash messages
 */
function displayFlashMessages($key = null) {
    $messages = getFlashMessages($key);
    if (empty($messages)) {
        return '';
    }
    
    $html = '';
    foreach ($messages as $messageKey => $data) {
        $typeClass = 'flash-' . $data['type'];
        $icon = [
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️'
        ][$data['type']] ?? 'ℹ️';
        
        $html .= sprintf(
            '<div class="flash-message %s" data-key="%s">
                <span class="flash-icon">%s</span>
                <span class="flash-text">%s</span>
                <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
            </div>',
            $typeClass,
            htmlspecialchars($messageKey),
            $icon,
            htmlspecialchars($data['message'])
        );
    }
    
    return $html;
}

/**
 * Remember user preference
 * 
 * @param string $key Preference key
 * @param mixed $value Preference value
 */
function setUserPreference($key, $value) {
    if (!isset($_SESSION['user_preferences'])) {
        $_SESSION['user_preferences'] = [];
    }
    
    $_SESSION['user_preferences'][$key] = $value;
}

/**
 * Get user preference
 * 
 * @param string $key Preference key
 * @param mixed $default Default value if not set
 * @return mixed Preference value
 */
function getUserPreference($key, $default = null) {
    return $_SESSION['user_preferences'][$key] ?? $default;
}

/**
 * Clear user preferences
 * 
 * @param string|null $key Specific key to clear or null for all
 */
function clearUserPreferences($key = null) {
    if ($key !== null) {
        unset($_SESSION['user_preferences'][$key]);
    } else {
        unset($_SESSION['user_preferences']);
    }
}

/**
 * Store previous URL for post-login redirect
 * 
 * @param string $url URL to store
 */
function storeIntendedUrl($url) {
    $_SESSION['intended_url'] = $url;
}

/**
 * Get and clear intended URL
 * 
 * @param string $default Default URL if none stored
 * @return string URL to redirect to
 */
function getIntendedUrl($default = null) {
    $url = $_SESSION['intended_url'] ?? $default;
    unset($_SESSION['intended_url']);
    return $url ?: AppConfig::getUrl();
}

/**
 * Check if there's a stored intended URL
 * 
 * @return bool True if URL is stored
 */
function hasIntendedUrl() {
    return isset($_SESSION['intended_url']);
}

/**
 * Store current URL as intended URL (for login redirects)
 */
function storeCurrentUrlAsIntended() {
    $currentUrl = getCurrentUrl();
    
    // Don't store login, logout, or API URLs
    $excludePatterns = [
        '/auth/',
        '/api/',
        'login',
        'logout'
    ];
    
    foreach ($excludePatterns as $pattern) {
        if (strpos($currentUrl, $pattern) !== false) {
            return;
        }
    }
    
    storeIntendedUrl($currentUrl);
}

/**
 * Initialize session-based rate limiting
 * 
 * @param string $action Action being rate limited
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if action is allowed
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    $key = $action;
    
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
    if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
        return false;
    }
    
    // Record this attempt
    $_SESSION['rate_limits'][$key][] = $now;
    
    return true;
}

/**
 * Get remaining rate limit attempts
 * 
 * @param string $action Action being checked
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return array Rate limit information
 */
function getRateLimitInfo($action, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limits'][$action])) {
        return [
            'remaining' => $maxAttempts,
            'reset_time' => null,
            'blocked' => false
        ];
    }
    
    $now = time();
    $attempts = $_SESSION['rate_limits'][$action];
    
    // Filter recent attempts
    $recentAttempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    $remaining = max(0, $maxAttempts - count($recentAttempts));
    $resetTime = empty($recentAttempts) ? null : min($recentAttempts) + $timeWindow;
    
    return [
        'remaining' => $remaining,
        'reset_time' => $resetTime,
        'blocked' => $remaining === 0
    ];
}

/**
 * Clear rate limit for specific action
 * 
 * @param string $action Action to clear
 */
function clearRateLimit($action) {
    unset($_SESSION['rate_limits'][$action]);
}

/**
 * Get session debug information (development only)
 * 
 * @return array Session debug information
 */
function getSessionDebugInfo() {
    if (!DEVELOPMENT_MODE) {
        return [];
    }
    
    $sessionSize = strlen(serialize($_SESSION));
    $sessionAge = isLoggedIn() ? (time() - $_SESSION['login_time']) : 0;
    $timeRemaining = getSessionTimeRemaining();
    
    return [
        'session_id' => session_id(),
        'session_size' => formatFileSize($sessionSize),
        'session_age' => $sessionAge . ' seconds',
        'time_remaining' => $timeRemaining ? $timeRemaining . ' seconds' : 'N/A',
        'is_logged_in' => isLoggedIn(),
        'user_type' => getCurrentUserType(),
        'user_id' => getCurrentUserId(),
        'ip_address' => getClientIP(),
        'last_activity' => isset($_SESSION['last_activity']) ? 
            date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'N/A',
        'csrf_token' => isset($_SESSION['csrf_token']) ? 
            substr($_SESSION['csrf_token'], 0, 8) . '...' : 'Not set',
        'flash_messages' => count($_SESSION['flash_messages'] ?? []),
        'preferences' => count($_SESSION['user_preferences'] ?? []),
        'rate_limits' => count($_SESSION['rate_limits'] ?? [])
    ];
}

/**
 * Clean up expired session data
 */
function cleanupSessionData() {
    // Remove old flash messages (older than 1 hour)
    if (isset($_SESSION['flash_messages'])) {
        $now = time();
        $_SESSION['flash_messages'] = array_filter(
            $_SESSION['flash_messages'],
            function($message) use ($now) {
                return ($now - $message['time']) < 3600;
            }
        );
    }
    
    // Clean up rate limits (remove expired entries)
    if (isset($_SESSION['rate_limits'])) {
        $now = time();
        foreach ($_SESSION['rate_limits'] as $action => $attempts) {
            $_SESSION['rate_limits'][$action] = array_filter(
                $attempts,
                function($timestamp) use ($now) {
                    return ($now - $timestamp) < 3600; // Keep for 1 hour max
                }
            );
            
            // Remove empty rate limit arrays
            if (empty($_SESSION['rate_limits'][$action])) {
                unset($_SESSION['rate_limits'][$action]);
            }
        }
    }
    
    // Remove old temporary data
    if (isset($_SESSION['temp_data'])) {
        $now = time();
        foreach ($_SESSION['temp_data'] as $key => $data) {
            if (isset($data['expires']) && $data['expires'] < $now) {
                unset($_SESSION['temp_data'][$key]);
            }
        }
    }
}

/**
 * Store temporary data in session with expiration
 * 
 * @param string $key Data key
 * @param mixed $value Data value
 * @param int $ttl Time to live in seconds (default: 1 hour)
 */
function setTempSessionData($key, $value, $ttl = 3600) {
    if (!isset($_SESSION['temp_data'])) {
        $_SESSION['temp_data'] = [];
    }
    
    $_SESSION['temp_data'][$key] = [
        'value' => $value,
        'expires' => time() + $ttl,
        'created' => time()
    ];
}

/**
 * Get temporary session data
 * 
 * @param string $key Data key
 * @param mixed $default Default value if not found or expired
 * @return mixed Data value or default
 */
function getTempSessionData($key, $default = null) {
    if (!isset($_SESSION['temp_data'][$key])) {
        return $default;
    }
    
    $data = $_SESSION['temp_data'][$key];
    
    // Check if expired
    if ($data['expires'] < time()) {
        unset($_SESSION['temp_data'][$key]);
        return $default;
    }
    
    return $data['value'];
}

/**
 * Clear temporary session data
 * 
 * @param string|null $key Specific key to clear or null for all
 */
function clearTempSessionData($key = null) {
    if ($key !== null) {
        unset($_SESSION['temp_data'][$key]);
    } else {
        unset($_SESSION['temp_data']);
    }
}

/**
 * Auto-cleanup session data (call this periodically)
 */
function autoCleanupSession() {
    // Only run cleanup occasionally to avoid performance impact
    if (!isset($_SESSION['last_cleanup']) || 
        ($_SESSION['last_cleanup'] < (time() - 300))) { // Every 5 minutes
        
        cleanupSessionData();
        $_SESSION['last_cleanup'] = time();
    }
}

/**
 * Session middleware function - call this on every request
 */
function sessionMiddleware() {
    // Update activity if logged in
    if (isLoggedIn()) {
        updateSessionActivity();
    }
    
    // Validate session security
    validateSessionSecurity();
    
    // Auto-cleanup old data
    autoCleanupSession();
    
    // Store current URL for potential login redirect
    if (!isLoggedIn() && isGetRequest() && !isAjaxRequest()) {
        storeCurrentUrlAsIntended();
    }
}

/**
 * Get user avatar URL or generate default
 * 
 * @param int|null $userId User ID (uses current user if null)
 * @param string|null $userType User type (uses current type if null)
 * @return string Avatar URL
 */
function getUserAvatarUrl($userId = null, $userType = null) {
    $userId = $userId ?: getCurrentUserId();
    $userType = $userType ?: getCurrentUserType();
    
    if (!$userId || !$userType) {
        return AppConfig::getAsset('images/default-avatar.png');
    }
    
    // Check for uploaded avatar
    $avatarPath = "uploads/avatars/{$userType}_{$userId}.jpg";
    $fullPath = ROOT_DIR . 'assets/' . $avatarPath;
    
    if (file_exists($fullPath)) {
        return AppConfig::getAsset($avatarPath);
    }
    
    // Generate gravatar for mentors (if they have email)
    if ($userType === 'mentor' && isset($_SESSION['email'])) {
        $hash = md5(strtolower(trim($_SESSION['email'])));
        return "https://www.gravatar.com/avatar/{$hash}?s=200&d=identicon";
    }
    
    // Return default avatar
    return AppConfig::getAsset('images/default-avatar.png');
}

/**
 * Get user display name
 * 
 * @return string User display name
 */
function getUserDisplayName() {
    $userType = getCurrentUserType();
    
    switch ($userType) {
        case 'admin':
            return 'Administrator';
            
        case 'mentor':
            return $_SESSION['name'] ?? 'Mentor';
            
        case 'project':
            return $_SESSION['project_name'] ?? 'Project Team';
            
        default:
            return 'Guest';
    }
}

/**
 * Check if user can access resource
 * 
 * @param string $resource Resource identifier
 * @param string $action Action being performed
 * @return bool True if access is allowed
 */
function canAccessResource($resource, $action = 'view') {
    $userType = getCurrentUserType();
    
    if (!$userType) {
        return false; // Not logged in
    }
    
    // Define access rules
    $accessRules = [
        'admin_dashboard' => ['admin'],
        'mentor_dashboard' => ['mentor'],
        'project_dashboard' => ['project'],
        'all_projects' => ['admin', 'mentor'],
        'project_management' => ['admin'],
        'mentor_management' => ['admin'],
        'project_rating' => ['mentor'],
        'project_termination' => ['admin'],
        'system_settings' => ['admin']
    ];
    
    $allowedTypes = $accessRules[$resource] ?? [];
    
    return in_array($userType, $allowedTypes);
}

// Auto-run session middleware when this file is loaded
sessionMiddleware();