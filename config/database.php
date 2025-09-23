<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Database Configuration File
 * 
 * This file contains all database connection settings and configuration
 * constants used throughout the application.
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
 * Database Configuration Class
 * 
 * Contains all database-related configuration constants and settings
 */
class DatabaseConfig {
    
    // Database Connection Settings
    const DB_HOST = 'localhost';
    const DB_NAME = 'jhub_africa_tracker';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_PORT = 3306;
    const DB_CHARSET = 'utf8mb4';
    const DB_COLLATION = 'utf8mb4_unicode_ci';
    
    // Security Settings
    const PASSWORD_HASH_COST = 12;
    const SESSION_LIFETIME = 3600; // 1 hour in seconds
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_LOCKOUT_TIME = 900; // 15 minutes in seconds
    
    // Application Settings
    const DEFAULT_PROJECT_STAGE = 1;
    const DEFAULT_PROJECT_PERCENTAGE = 10;
    const MAX_COMMENT_LENGTH = 5000;
    const MAX_PROJECT_NAME_LENGTH = 200;
    const MAX_UPLOAD_SIZE = 5242880; // 5MB in bytes
    
    // Pagination Settings
    const DEFAULT_PAGE_SIZE = 12;
    const MAX_PAGE_SIZE = 50;
    
    // Stage Configuration
    const PROJECT_STAGES = [
        1 => [
            'name' => 'Welcome and Introduction',
            'percentage' => 10,
            'description' => 'Registration, profile setup, and community integration'
        ],
        2 => [
            'name' => 'Assessment and Personalization',
            'percentage' => 20,
            'description' => 'Skills assessment and personalized learning path creation'
        ],
        3 => [
            'name' => 'Learning and Development',
            'percentage' => 20,
            'description' => 'Core training modules and practical application'
        ],
        4 => [
            'name' => 'Mentorship and Support',
            'percentage' => 10,
            'description' => 'One-on-one mentorship and peer-to-peer learning'
        ],
        5 => [
            'name' => 'Progress Tracking and Feedback',
            'percentage' => 20,
            'description' => 'Milestone reviews and MVP development'
        ],
        6 => [
            'name' => 'Showcase and Integration',
            'percentage' => 20,
            'description' => 'Project showcase and ecosystem integration'
        ]
    ];
    
    // User Types and Permissions
    const USER_TYPES = [
        'admin' => [
            'name' => 'Administrator',
            'permissions' => ['view_all', 'terminate_projects', 'manage_mentors', 'system_settings']
        ],
        'mentor' => [
            'name' => 'Mentor',
            'permissions' => ['view_projects', 'rate_projects', 'comment_projects', 'self_assign']
        ],
        'project' => [
            'name' => 'Project Team',
            'permissions' => ['view_own_project', 'manage_team', 'comment_own_project']
        ],
        'public' => [
            'name' => 'Public User',
            'permissions' => ['view_projects', 'comment_projects', 'create_projects']
        ]
    ];
    
    // File Upload Settings
    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt'];
    const UPLOAD_PATH = 'assets/uploads/';
    
    // Email Configuration (if implementing email notifications)
    const SMTP_HOST = '';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = '';
    const SMTP_PASSWORD = '';
    const FROM_EMAIL = 'noreply@jhubafrica.com';
    const FROM_NAME = 'JHUB AFRICA';
    
    // Cache Settings
    const CACHE_ENABLED = false;
    const CACHE_LIFETIME = 3600; // 1 hour
    
    // API Settings
    const API_RATE_LIMIT = 100; // requests per hour per IP
    const API_VERSION = 'v1';
    
    // Development/Production Settings
    const ENVIRONMENT = 'development'; // 'development' or 'production'
    const DEBUG_MODE = true;
    const LOG_ERRORS = true;
    const DISPLAY_ERRORS = false; // Should be false in production
    
    /**
     * Get database DSN string
     * 
     * @return string Database connection string
     */
    public static function getDSN() {
        return sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME,
            self::DB_CHARSET
        );
    }
    
    /**
     * Get database connection options
     * 
     * @return array PDO connection options
     */
    public static function getOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET . " COLLATE " . self::DB_COLLATION
        ];
    }
    
    /**
     * Get stage information by stage number
     * 
     * @param int $stage Stage number (1-6)
     * @return array|null Stage information or null if not found
     */
    public static function getStageInfo($stage) {
        return self::PROJECT_STAGES[$stage] ?? null;
    }
    
    /**
     * Get all project stages
     * 
     * @return array All project stages
     */
    public static function getAllStages() {
        return self::PROJECT_STAGES;
    }
    
    /**
     * Check if user type is valid
     * 
     * @param string $userType User type to check
     * @return bool True if valid, false otherwise
     */
    public static function isValidUserType($userType) {
        return isset(self::USER_TYPES[$userType]);
    }
    
    /**
     * Get user permissions by user type
     * 
     * @param string $userType User type
     * @return array User permissions
     */
    public static function getUserPermissions($userType) {
        return self::USER_TYPES[$userType]['permissions'] ?? [];
    }
    
    /**
     * Check if user has specific permission
     * 
     * @param string $userType User type
     * @param string $permission Permission to check
     * @return bool True if user has permission, false otherwise
     */
    public static function userHasPermission($userType, $permission) {
        $permissions = self::getUserPermissions($userType);
        return in_array($permission, $permissions);
    }
    
    /**
     * Get environment-specific settings
     * 
     * @return array Environment settings
     */
    public static function getEnvironmentSettings() {
        if (self::ENVIRONMENT === 'production') {
            return [
                'debug' => false,
                'display_errors' => false,
                'log_errors' => true,
                'error_reporting' => E_ERROR | E_WARNING | E_PARSE
            ];
        } else {
            return [
                'debug' => true,
                'display_errors' => true,
                'log_errors' => true,
                'error_reporting' => E_ALL
            ];
        }
    }
    
    /**
     * Initialize environment settings
     */
    public static function initializeEnvironment() {
        $settings = self::getEnvironmentSettings();
        
        ini_set('display_errors', $settings['display_errors'] ? 1 : 0);
        ini_set('log_errors', $settings['log_errors'] ? 1 : 0);
        error_reporting($settings['error_reporting']);
        
        // Set timezone
        date_default_timezone_set('Africa/Nairobi');
        
        // Set memory limit
        ini_set('memory_limit', '256M');
        
        // Set max execution time
        ini_set('max_execution_time', 30);
    }
}

// Initialize environment on load
DatabaseConfig::initializeEnvironment();