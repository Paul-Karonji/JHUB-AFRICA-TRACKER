<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Application Configuration File
 * 
 * This file contains application-wide settings, constants, and configuration
 * that are used throughout the JHUB AFRICA system.
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
 * Application Configuration Class
 * 
 * Contains all application-wide configuration settings
 */
class AppConfig {
    
    // Application Information
    const APP_NAME = 'JHUB AFRICA Project Tracker';
    const APP_VERSION = '1.0.0';
    const APP_DESCRIPTION = 'Innovation Management System for African Entrepreneurs';
    const APP_URL = 'https://tracker.jhubafrica.com';
    const COMPANY_NAME = 'JHUB AFRICA';
    const COMPANY_WEBSITE = 'https://jhubafrica.com';
    
    // System Paths
    const ROOT_PATH = __DIR__ . '/../';
    const CLASSES_PATH = self::ROOT_PATH . 'classes/';
    const INCLUDES_PATH = self::ROOT_PATH . 'includes/';
    const TEMPLATES_PATH = self::ROOT_PATH . 'templates/';
    const UPLOADS_PATH = self::ROOT_PATH . 'assets/uploads/';
    const LOGS_PATH = self::ROOT_PATH . 'logs/';
    
    // URL Paths
    const BASE_URL = '/jhub-africa-tracker/'; // Adjust based on your installation
    const ASSETS_URL = self::BASE_URL . 'assets/';
    const API_URL = self::BASE_URL . 'api/';
    const UPLOADS_URL = self::BASE_URL . 'assets/uploads/';
    
    // Dashboard URLs
    const ADMIN_DASHBOARD_URL = self::BASE_URL . 'dashboards/admin/';
    const MENTOR_DASHBOARD_URL = self::BASE_URL . 'dashboards/mentor/';
    const PROJECT_DASHBOARD_URL = self::BASE_URL . 'dashboards/project/';
    
    // Authentication URLs
    const LOGIN_URL = self::BASE_URL . 'auth/login.php';
    const LOGOUT_URL = self::BASE_URL . 'auth/logout.php';
    const ADMIN_LOGIN_URL = self::BASE_URL . 'auth/admin-login.php';
    const MENTOR_LOGIN_URL = self::BASE_URL . 'auth/mentor-login.php';
    const PROJECT_LOGIN_URL = self::BASE_URL . 'auth/project-login.php';
    
    // Session Configuration
    const SESSION_NAME = 'JHUB_SESSION';
    const SESSION_COOKIE_LIFETIME = 3600; // 1 hour
    const SESSION_COOKIE_PATH = '/';
    const SESSION_COOKIE_DOMAIN = '';
    const SESSION_COOKIE_SECURE = false; // Set to true for HTTPS
    const SESSION_COOKIE_HTTPONLY = true;
    
    // Security Settings
    const CSRF_TOKEN_NAME = 'csrf_token';
    const ENCRYPT_KEY = 'jhub-africa-2024-secure-key'; // Change this in production
    const SALT = 'jhub-salt-2024'; // Change this in production
    
    // File Upload Settings
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const ALLOWED_DOCUMENT_TYPES = ['application/pdf', 'text/plain'];
    const AVATAR_MAX_WIDTH = 500;
    const AVATAR_MAX_HEIGHT = 500;
    
    // Pagination Settings
    const PROJECTS_PER_PAGE = 12;
    const COMMENTS_PER_PAGE = 20;
    const NOTIFICATIONS_PER_PAGE = 15;
    const MENTORS_PER_PAGE = 10;
    
    // UI Settings
    const SITE_LOGO = 'assets/images/logo.png';
    const DEFAULT_AVATAR = 'assets/images/default-avatar.png';
    const FAVICON = 'assets/images/favicon.ico';
    
    // Theme Colors (for dynamic styling)
    const PRIMARY_COLOR = '#1565c0';
    const SECONDARY_COLOR = '#42a5f5';
    const SUCCESS_COLOR = '#4caf50';
    const WARNING_COLOR = '#ff9800';
    const ERROR_COLOR = '#f44336';
    const INFO_COLOR = '#2196f3';
    
    // Email Templates
    const EMAIL_TEMPLATES = [
        'welcome_mentor' => 'Welcome to JHUB AFRICA - Mentor Registration',
        'project_assigned' => 'New Project Assignment',
        'project_completed' => 'Project Completion Notification',
        'mentor_joined' => 'New Mentor Joined Your Project',
        'rating_updated' => 'Your Project Rating Has Been Updated'
    ];
    
    // Notification Types
    const NOTIFICATION_TYPES = [
        'mentor_joined' => [
            'icon' => '🏆',
            'color' => 'success',
            'title_template' => 'New Mentor Joined'
        ],
        'project_created' => [
            'icon' => '🚀',
            'color' => 'info',
            'title_template' => 'New Project Created'
        ],
        'rating_updated' => [
            'icon' => '📈',
            'color' => 'primary',
            'title_template' => 'Project Rating Updated'
        ],
        'project_completed' => [
            'icon' => '🎉',
            'color' => 'success',
            'title_template' => 'Project Completed'
        ],
        'project_terminated' => [
            'icon' => '⚠️',
            'color' => 'warning',
            'title_template' => 'Project Terminated'
        ],
        'comment_added' => [
            'icon' => '💬',
            'color' => 'info',
            'title_template' => 'New Comment Added'
        ]
    ];
    
    // Social Media Links
    const SOCIAL_LINKS = [
        'facebook' => 'https://facebook.com/jhubafrica',
        'twitter' => 'https://twitter.com/jhubafrica',
        'linkedin' => 'https://linkedin.com/company/jhubafrica',
        'instagram' => 'https://instagram.com/jhubafrica'
    ];
    
    // Contact Information
    const CONTACT_EMAIL = 'info@jhubafrica.com';
    const SUPPORT_EMAIL = 'support@jhubafrica.com';
    const CONTACT_PHONE = '+254-XXX-XXX-XXX';
    const CONTACT_ADDRESS = 'Nairobi, Kenya';
    
    // Default Admin Credentials (Change after first login)
    const DEFAULT_ADMIN_USERNAME = 'admin';
    const DEFAULT_ADMIN_PASSWORD = 'JhubAfrica2024!';
    
    // Cache Settings
    const ENABLE_CACHING = false;
    const CACHE_DURATION = 3600; // 1 hour
    const CACHE_DRIVER = 'file'; // 'file', 'redis', 'memcached'
    
    // Logging Settings
    const LOG_LEVEL = 'INFO'; // DEBUG, INFO, WARNING, ERROR
    const LOG_MAX_SIZE = 10485760; // 10MB
    const LOG_MAX_FILES = 5;
    
    // API Settings
    const API_RATE_LIMIT_ENABLED = true;
    const API_RATE_LIMIT_REQUESTS = 100;
    const API_RATE_LIMIT_PERIOD = 3600; // 1 hour
    const API_CORS_ENABLED = true;
    const API_CORS_ORIGINS = ['*']; // Restrict in production
    
    // Feature Flags
    const FEATURES = [
        'email_notifications' => false,
        'file_uploads' => true,
        'public_comments' => true,
        'project_analytics' => false,
        'advanced_search' => false,
        'export_data' => false,
        'backup_system' => false
    ];
    
    /**
     * Get full URL path
     * 
     * @param string $path Relative path
     * @return string Full URL
     */
    public static function getUrl($path = '') {
        return rtrim(self::BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    
    /**
     * Get assets URL
     * 
     * @param string $asset Asset path
     * @return string Full asset URL
     */
    public static function getAsset($asset) {
        return self::ASSETS_URL . ltrim($asset, '/');
    }
    
    /**
     * Get upload URL
     * 
     * @param string $file Upload file path
     * @return string Full upload URL
     */
    public static function getUpload($file) {
        return self::UPLOADS_URL . ltrim($file, '/');
    }
    
    /**
     * Check if feature is enabled
     * 
     * @param string $feature Feature name
     * @return bool True if enabled, false otherwise
     */
    public static function isFeatureEnabled($feature) {
        return self::FEATURES[$feature] ?? false;
    }
    
    /**
     * Get notification configuration
     * 
     * @param string $type Notification type
     * @return array|null Notification config or null if not found
     */
    public static function getNotificationConfig($type) {
        return self::NOTIFICATION_TYPES[$type] ?? null;
    }
    
    /**
     * Get dashboard URL by user type
     * 
     * @param string $userType User type (admin, mentor, project)
     * @return string Dashboard URL
     */
    public static function getDashboardUrl($userType) {
        switch ($userType) {
            case 'admin':
                return self::ADMIN_DASHBOARD_URL;
            case 'mentor':
                return self::MENTOR_DASHBOARD_URL;
            case 'project':
                return self::PROJECT_DASHBOARD_URL;
            default:
                return self::BASE_URL;
        }
    }
    
    /**
     * Get login URL by user type
     * 
     * @param string $userType User type (admin, mentor, project)
     * @return string Login URL
     */
    public static function getLoginUrl($userType = null) {
        switch ($userType) {
            case 'admin':
                return self::ADMIN_LOGIN_URL;
            case 'mentor':
                return self::MENTOR_LOGIN_URL;
            case 'project':
                return self::PROJECT_LOGIN_URL;
            default:
                return self::LOGIN_URL;
        }
    }
    
    /**
     * Initialize session configuration
     */
    public static function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session configuration
            ini_set('session.cookie_lifetime', self::SESSION_COOKIE_LIFETIME);
            ini_set('session.cookie_path', self::SESSION_COOKIE_PATH);
            ini_set('session.cookie_domain', self::SESSION_COOKIE_DOMAIN);
            ini_set('session.cookie_secure', self::SESSION_COOKIE_SECURE ? 1 : 0);
            ini_set('session.cookie_httponly', self::SESSION_COOKIE_HTTPONLY ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            session_name(self::SESSION_NAME);
            session_start();
        }
    }
    
    /**
     * Get current environment
     * 
     * @return string Environment name
     */
    public static function getEnvironment() {
        return DatabaseConfig::ENVIRONMENT;
    }
    
    /**
     * Check if in development mode
     * 
     * @return bool True if development, false otherwise
     */
    public static function isDevelopment() {
        return self::getEnvironment() === 'development';
    }
    
    /**
     * Check if in production mode
     * 
     * @return bool True if production, false otherwise
     */
    public static function isProduction() {
        return self::getEnvironment() === 'production';
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[self::CSRF_TOKEN_NAME])) {
            $_SESSION[self::CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid, false otherwise
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION[self::CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[self::CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Get application information
     * 
     * @return array Application info
     */
    public static function getAppInfo() {
        return [
            'name' => self::APP_NAME,
            'version' => self::APP_VERSION,
            'description' => self::APP_DESCRIPTION,
            'url' => self::APP_URL,
            'company' => self::COMPANY_NAME,
            'company_website' => self::COMPANY_WEBSITE
        ];
    }
    
    /**
     * Create directory if it doesn't exist
     * 
     * @param string $path Directory path
     * @param int $permissions Directory permissions
     * @return bool True if created or exists, false on failure
     */
    public static function createDirectory($path, $permissions = 0755) {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }
    
    /**
     * Initialize required directories
     */
    public static function initializeDirectories() {
        $directories = [
            self::UPLOADS_PATH,
            self::UPLOADS_PATH . 'projects/',
            self::UPLOADS_PATH . 'profiles/',
            self::UPLOADS_PATH . 'documents/',
            self::LOGS_PATH
        ];
        
        foreach ($directories as $dir) {
            self::createDirectory($dir);
            
            // Create index.html to prevent directory listing
            $indexFile = $dir . 'index.html';
            if (!file_exists($indexFile)) {
                file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1></body></html>');
            }
        }
    }
}

// Auto-initialize session when config is loaded
AppConfig::initializeSession();
AppConfig::initializeDirectories();