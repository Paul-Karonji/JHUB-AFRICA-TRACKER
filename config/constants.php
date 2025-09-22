<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * System Constants File
 * 
 * This file defines all system-wide constants used throughout the application.
 * These constants provide consistency and make the code more maintainable.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Prevent direct access
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

// ==============================================
// SYSTEM CONSTANTS
// ==============================================

// Application Access Control
define('JHUB_ACCESS', true);
define('JHUB_VERSION', '1.0.0');
define('JHUB_RELEASE_DATE', '2024-01-01');

// ==============================================
// PROJECT STAGE CONSTANTS
// ==============================================

define('STAGE_WELCOME', 1);
define('STAGE_ASSESSMENT', 2);
define('STAGE_LEARNING', 3);
define('STAGE_MENTORSHIP', 4);
define('STAGE_PROGRESS', 5);
define('STAGE_SHOWCASE', 6);

// Stage Names
define('STAGE_NAMES', [
    STAGE_WELCOME => 'Welcome and Introduction',
    STAGE_ASSESSMENT => 'Assessment and Personalization',
    STAGE_LEARNING => 'Learning and Development',
    STAGE_MENTORSHIP => 'Mentorship and Support',
    STAGE_PROGRESS => 'Progress Tracking and Feedback',
    STAGE_SHOWCASE => 'Showcase and Integration'
]);

// Stage Percentages
define('STAGE_PERCENTAGES', [
    STAGE_WELCOME => 10,
    STAGE_ASSESSMENT => 20,
    STAGE_LEARNING => 20,
    STAGE_MENTORSHIP => 10,
    STAGE_PROGRESS => 20,
    STAGE_SHOWCASE => 20
]);

// ==============================================
// USER TYPE CONSTANTS
// ==============================================

define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_MENTOR', 'mentor');
define('USER_TYPE_PROJECT', 'project');
define('USER_TYPE_PUBLIC', 'public');

// User Role Levels (for permission hierarchy)
define('ROLE_LEVELS', [
    USER_TYPE_PUBLIC => 1,
    USER_TYPE_PROJECT => 2,
    USER_TYPE_MENTOR => 3,
    USER_TYPE_ADMIN => 4
]);

// ==============================================
// PROJECT STATUS CONSTANTS
// ==============================================

define('PROJECT_STATUS_ACTIVE', 'active');
define('PROJECT_STATUS_COMPLETED', 'completed');
define('PROJECT_STATUS_TERMINATED', 'terminated');
define('PROJECT_STATUS_DRAFT', 'draft');

// Status Colors (for UI)
define('STATUS_COLORS', [
    PROJECT_STATUS_ACTIVE => 'success',
    PROJECT_STATUS_COMPLETED => 'info',
    PROJECT_STATUS_TERMINATED => 'danger',
    PROJECT_STATUS_DRAFT => 'warning'
]);

// ==============================================
// NOTIFICATION CONSTANTS
// ==============================================

define('NOTIFICATION_MENTOR_JOINED', 'mentor_joined');
define('NOTIFICATION_PROJECT_CREATED', 'project_created');
define('NOTIFICATION_RATING_UPDATED', 'rating_updated');
define('NOTIFICATION_PROJECT_COMPLETED', 'project_completed');
define('NOTIFICATION_PROJECT_TERMINATED', 'project_terminated');
define('NOTIFICATION_COMMENT_ADDED', 'comment_added');
define('NOTIFICATION_INNOVATOR_ADDED', 'innovator_added');

// ==============================================
// COMMENT CONSTANTS
// ==============================================

define('COMMENT_TYPE_MAIN', 'main');
define('COMMENT_TYPE_REPLY', 'reply');
define('COMMENT_MAX_DEPTH', 3); // Maximum reply depth
define('COMMENT_MIN_LENGTH', 5);
define('COMMENT_MAX_LENGTH', 5000);

// ==============================================
// FILE UPLOAD CONSTANTS
// ==============================================

// File Types
define('UPLOAD_TYPE_AVATAR', 'avatar');
define('UPLOAD_TYPE_DOCUMENT', 'document');
define('UPLOAD_TYPE_IMAGE', 'image');

// File Size Limits (in bytes)
define('MAX_AVATAR_SIZE', 2097152); // 2MB
define('MAX_DOCUMENT_SIZE', 10485760); // 10MB
define('MAX_IMAGE_SIZE', 5242880); // 5MB

// Allowed Extensions
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'rtf']);

// ==============================================
// VALIDATION CONSTANTS
// ==============================================

// Password Requirements
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PASSWORD_LENGTH', 128);
define('REQUIRE_PASSWORD_UPPERCASE', false);
define('REQUIRE_PASSWORD_LOWERCASE', false);
define('REQUIRE_PASSWORD_NUMBERS', false);
define('REQUIRE_PASSWORD_SPECIAL', false);

// Username/Profile Name Requirements
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 50);
define('USERNAME_PATTERN', '/^[a-zA-Z0-9_-]+$/');

// Email Requirements
define('MAX_EMAIL_LENGTH', 150);

// Name Requirements
define('MIN_NAME_LENGTH', 2);
define('MAX_NAME_LENGTH', 100);
define('NAME_PATTERN', '/^[a-zA-Z\s\'-\.]+$/u');

// Project Name Requirements
define('MIN_PROJECT_NAME_LENGTH', 5);
define('MAX_PROJECT_NAME_LENGTH', 200);

// ==============================================
// ERROR CONSTANTS
// ==============================================

// Error Types
define('ERROR_TYPE_VALIDATION', 'validation');
define('ERROR_TYPE_AUTHENTICATION', 'authentication');
define('ERROR_TYPE_AUTHORIZATION', 'authorization');
define('ERROR_TYPE_DATABASE', 'database');
define('ERROR_TYPE_SYSTEM', 'system');

// Error Codes
define('ERROR_CODE_INVALID_INPUT', 1001);
define('ERROR_CODE_MISSING_FIELD', 1002);
define('ERROR_CODE_INVALID_CREDENTIALS', 2001);
define('ERROR_CODE_SESSION_EXPIRED', 2002);
define('ERROR_CODE_ACCESS_DENIED', 3001);
define('ERROR_CODE_INSUFFICIENT_PERMISSIONS', 3002);
define('ERROR_CODE_DATABASE_ERROR', 4001);
define('ERROR_CODE_CONNECTION_FAILED', 4002);
define('ERROR_CODE_SYSTEM_ERROR', 5001);
define('ERROR_CODE_FILE_ERROR', 5002);

// ==============================================
// SUCCESS CONSTANTS
// ==============================================

define('SUCCESS_PROJECT_CREATED', 'Project created successfully');
define('SUCCESS_MENTOR_REGISTERED', 'Mentor registered successfully');
define('SUCCESS_INNOVATOR_ADDED', 'Team member added successfully');
define('SUCCESS_COMMENT_ADDED', 'Comment added successfully');
define('SUCCESS_RATING_UPDATED', 'Project rating updated successfully');
define('SUCCESS_LOGIN', 'Login successful');
define('SUCCESS_LOGOUT', 'Logged out successfully');

// ==============================================
// PAGINATION CONSTANTS
// ==============================================

define('DEFAULT_PAGE', 1);
define('DEFAULT_ITEMS_PER_PAGE', 12);
define('MAX_ITEMS_PER_PAGE', 100);

// ==============================================
// DATE/TIME CONSTANTS
// ==============================================

define('DEFAULT_TIMEZONE', 'Africa/Nairobi');
define('DEFAULT_DATE_FORMAT', 'Y-m-d');
define('DEFAULT_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M j, Y');
define('DISPLAY_DATETIME_FORMAT', 'M j, Y \a\t g:i A');

// ==============================================
// CACHE CONSTANTS
// ==============================================

define('CACHE_PREFIX', 'jhub_');
define('CACHE_TTL_SHORT', 300); // 5 minutes
define('CACHE_TTL_MEDIUM', 1800); // 30 minutes
define('CACHE_TTL_LONG', 3600); // 1 hour
define('CACHE_TTL_VERY_LONG', 86400); // 24 hours

// ==============================================
// API CONSTANTS
// ==============================================

define('API_VERSION', 'v1');
define('API_SUCCESS', 'success');
define('API_ERROR', 'error');

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_CONFLICT', 409);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_INTERNAL_SERVER_ERROR', 500);

// ==============================================
// SECURITY CONSTANTS
// ==============================================

define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_RESET_TOKEN_EXPIRY', 3600); // 1 hour

// ==============================================
// LOGGING CONSTANTS
// ==============================================

define('LOG_LEVEL_DEBUG', 1);
define('LOG_LEVEL_INFO', 2);
define('LOG_LEVEL_WARNING', 3);
define('LOG_LEVEL_ERROR', 4);
define('LOG_LEVEL_CRITICAL', 5);

define('LOG_LEVELS', [
    LOG_LEVEL_DEBUG => 'DEBUG',
    LOG_LEVEL_INFO => 'INFO',
    LOG_LEVEL_WARNING => 'WARNING',
    LOG_LEVEL_ERROR => 'ERROR',
    LOG_LEVEL_CRITICAL => 'CRITICAL'
]);

// ==============================================
// EMAIL CONSTANTS
// ==============================================

define('EMAIL_TYPE_WELCOME', 'welcome');
define('EMAIL_TYPE_NOTIFICATION', 'notification');
define('EMAIL_TYPE_REMINDER', 'reminder');
define('EMAIL_TYPE_REPORT', 'report');

// ==============================================
// UTILITY FUNCTIONS FOR CONSTANTS
// ==============================================

/**
 * Get stage name by stage number
 * 
 * @param int $stage Stage number
 * @return string Stage name
 */
function getStageName($stage) {
    return STAGE_NAMES[$stage] ?? 'Unknown Stage';
}

/**
 * Get stage percentage by stage number
 * 
 * @param int $stage Stage number
 * @return int Stage percentage
 */
function getStagePercentage($stage) {
    return STAGE_PERCENTAGES[$stage] ?? 0;
}

/**
 * Check if stage is valid
 * 
 * @param int $stage Stage number to check
 * @return bool True if valid, false otherwise
 */
function isValidStage($stage) {
    return isset(STAGE_NAMES[$stage]);
}

/**
 * Check if user type is valid
 * 
 * @param string $userType User type to check
 * @return bool True if valid, false otherwise
 */
function isValidUserType($userType) {
    return in_array($userType, [USER_TYPE_ADMIN, USER_TYPE_MENTOR, USER_TYPE_PROJECT, USER_TYPE_PUBLIC]);
}

/**
 * Check if project status is valid
 * 
 * @param string $status Status to check
 * @return bool True if valid, false otherwise
 */
function isValidProjectStatus($status) {
    return in_array($status, [PROJECT_STATUS_ACTIVE, PROJECT_STATUS_COMPLETED, PROJECT_STATUS_TERMINATED, PROJECT_STATUS_DRAFT]);
}

/**
 * Get user role level for permission comparison
 * 
 * @param string $userType User type
 * @return int Role level
 */
function getUserRoleLevel($userType) {
    return ROLE_LEVELS[$userType] ?? 0;
}

/**
 * Check if user has sufficient role level
 * 
 * @param string $userType User's type
 * @param string $requiredType Required minimum type
 * @return bool True if sufficient, false otherwise
 */
function hasRequiredRole($userType, $requiredType) {
    return getUserRoleLevel($userType) >= getUserRoleLevel($requiredType);
}

/**
 * Get HTTP status code description
 * 
 * @param int $code HTTP status code
 * @return string Status description
 */
function getHttpStatusText($code) {
    $statuses = [
        HTTP_OK => 'OK',
        HTTP_CREATED => 'Created',
        HTTP_BAD_REQUEST => 'Bad Request',
        HTTP_UNAUTHORIZED => 'Unauthorized',
        HTTP_FORBIDDEN => 'Forbidden',
        HTTP_NOT_FOUND => 'Not Found',
        HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        HTTP_CONFLICT => 'Conflict',
        HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error'
    ];
    
    return $statuses[$code] ?? 'Unknown Status';
}

/**
 * Format file size in human readable format
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Check if file extension is allowed for specific type
 * 
 * @param string $extension File extension
 * @param string $type Upload type
 * @return bool True if allowed, false otherwise
 */
function isAllowedExtension($extension, $type) {
    $extension = strtolower($extension);
    
    switch ($type) {
        case UPLOAD_TYPE_IMAGE:
        case UPLOAD_TYPE_AVATAR:
            return in_array($extension, ALLOWED_IMAGE_EXTENSIONS);
        case UPLOAD_TYPE_DOCUMENT:
            return in_array($extension, ALLOWED_DOCUMENT_EXTENSIONS);
        default:
            return false;
    }
}

// ==============================================
// APPLICATION INITIALIZATION
// ==============================================

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Set internal encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}