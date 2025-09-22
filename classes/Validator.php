<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Input Validation Class
 * 
 * This class provides comprehensive input validation for all data
 * entering the system, ensuring data integrity and security.
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
 * Validator Class
 * 
 * Provides static methods for validating various types of input data
 */
class Validator {
    
    /** @var array Validation errors */
    private static $errors = [];
    
    /**
     * Validate required fields
     * 
     * @param array $data Input data
     * @param array $required Required field names
     * @return bool True if all required fields present
     */
    public static function required(array $data, array $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            self::$errors[] = "Missing required fields: " . implode(', ', $missing);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @param bool $required Whether email is required
     * @return bool True if valid
     */
    public static function email($email, $required = true) {
        if (!$required && empty($email)) {
            return true;
        }
        
        if (empty($email)) {
            self::$errors[] = "Email address is required";
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$errors[] = "Invalid email address format";
            return false;
        }
        
        if (strlen($email) > MAX_EMAIL_LENGTH) {
            self::$errors[] = "Email address too long (max " . MAX_EMAIL_LENGTH . " characters)";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate URL
     * 
     * @param string $url URL to validate
     * @param bool $required Whether URL is required
     * @return bool True if valid
     */
    public static function url($url, $required = true) {
        if (!$required && empty($url)) {
            return true;
        }
        
        if (empty($url)) {
            self::$errors[] = "URL is required";
            return false;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            self::$errors[] = "Invalid URL format";
            return false;
        }
        
        // Check for allowed protocols
        $allowedProtocols = ['http', 'https'];
        $protocol = parse_url($url, PHP_URL_SCHEME);
        
        if (!in_array($protocol, $allowedProtocols)) {
            self::$errors[] = "URL must use HTTP or HTTPS protocol";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate string length
     * 
     * @param string $value String to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @param string $fieldName Field name for error messages
     * @return bool True if valid
     */
    public static function length($value, $min = 0, $max = null, $fieldName = 'Field') {
        $length = strlen(trim($value));
        
        if ($length < $min) {
            self::$errors[] = "$fieldName must be at least $min characters long";
            return false;
        }
        
        if ($max !== null && $length > $max) {
            self::$errors[] = "$fieldName must not exceed $max characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate username/profile name
     * 
     * @param string $username Username to validate
     * @return bool True if valid
     */
    public static function username($username) {
        if (empty($username)) {
            self::$errors[] = "Username is required";
            return false;
        }
        
        if (!self::length($username, MIN_USERNAME_LENGTH, MAX_USERNAME_LENGTH, "Username")) {
            return false;
        }
        
        if (!preg_match(USERNAME_PATTERN, $username)) {
            self::$errors[] = "Username can only contain letters, numbers, underscores, and hyphens";
            return false;
        }
        
        // Check for reserved usernames
        $reserved = ['admin', 'root', 'system', 'api', 'www', 'ftp', 'mail', 'support'];
        if (in_array(strtolower($username), $reserved)) {
            self::$errors[] = "Username is reserved and cannot be used";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password
     * 
     * @param string $password Password to validate
     * @return bool True if valid
     */
    public static function password($password) {
        if (empty($password)) {
            self::$errors[] = "Password is required";
            return false;
        }
        
        if (!self::length($password, MIN_PASSWORD_LENGTH, MAX_PASSWORD_LENGTH, "Password")) {
            return false;
        }
        
        // Check password strength requirements if enabled
        if (REQUIRE_PASSWORD_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            self::$errors[] = "Password must contain at least one uppercase letter";
            return false;
        }
        
        if (REQUIRE_PASSWORD_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            self::$errors[] = "Password must contain at least one lowercase letter";
            return false;
        }
        
        if (REQUIRE_PASSWORD_NUMBERS && !preg_match('/[0-9]/', $password)) {
            self::$errors[] = "Password must contain at least one number";
            return false;
        }
        
        if (REQUIRE_PASSWORD_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            self::$errors[] = "Password must contain at least one special character";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate person name
     * 
     * @param string $name Name to validate
     * @return bool True if valid
     */
    public static function name($name) {
        if (empty($name)) {
            self::$errors[] = "Name is required";
            return false;
        }
        
        if (!self::length($name, MIN_NAME_LENGTH, MAX_NAME_LENGTH, "Name")) {
            return false;
        }
        
        if (!preg_match(NAME_PATTERN, $name)) {
            self::$errors[] = "Name contains invalid characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate project name
     * 
     * @param string $name Project name to validate
     * @return bool True if valid
     */
    public static function projectName($name) {
        if (empty($name)) {
            self::$errors[] = "Project name is required";
            return false;
        }
        
        if (!self::length($name, MIN_PROJECT_NAME_LENGTH, MAX_PROJECT_NAME_LENGTH, "Project name")) {
            return false;
        }
        
        // Check for inappropriate content (basic check)
        $inappropriate = ['test', 'sample', 'demo', 'placeholder'];
        $lowerName = strtolower($name);
        
        foreach ($inappropriate as $word) {
            if (strpos($lowerName, $word) === 0) {
                self::$errors[] = "Project name should not start with '$word'";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate integer within range
     * 
     * @param mixed $value Value to validate
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param string $fieldName Field name for errors
     * @return bool True if valid
     */
    public static function intRange($value, $min = null, $max = null, $fieldName = 'Value') {
        if (!is_numeric($value)) {
            self::$errors[] = "$fieldName must be a number";
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            self::$errors[] = "$fieldName must be at least $min";
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            self::$errors[] = "$fieldName must not exceed $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate project stage
     * 
     * @param mixed $stage Stage to validate
     * @return bool True if valid
     */
    public static function projectStage($stage) {
        if (!self::intRange($stage, 1, 6, "Project stage")) {
            return false;
        }
        
        if (!isValidStage((int)$stage)) {
            self::$errors[] = "Invalid project stage";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate percentage value
     * 
     * @param mixed $percentage Percentage to validate
     * @return bool True if valid
     */
    public static function percentage($percentage) {
        return self::intRange($percentage, 0, 100, "Percentage");
    }
    
    /**
     * Validate date string
     * 
     * @param string $date Date string to validate
     * @param string $format Expected date format
     * @return bool True if valid
     */
    public static function date($date, $format = 'Y-m-d') {
        if (empty($date)) {
            self::$errors[] = "Date is required";
            return false;
        }
        
        $dateTime = DateTime::createFromFormat($format, $date);
        
        if (!$dateTime || $dateTime->format($format) !== $date) {
            self::$errors[] = "Invalid date format. Expected format: $format";
            return false;
        }
        
        // Check if date is not too old (more than 10 years ago)
        $tenYearsAgo = new DateTime('-10 years');
        if ($dateTime < $tenYearsAgo) {
            self::$errors[] = "Date cannot be more than 10 years ago";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate user type
     * 
     * @param string $userType User type to validate
     * @return bool True if valid
     */
    public static function userType($userType) {
        if (!isValidUserType($userType)) {
            self::$errors[] = "Invalid user type";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate project status
     * 
     * @param string $status Project status to validate
     * @return bool True if valid
     */
    public static function projectStatus($status) {
        if (!isValidProjectStatus($status)) {
            self::$errors[] = "Invalid project status";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate text content (descriptions, comments, etc.)
     * 
     * @param string $text Text to validate
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @param string $fieldName Field name for errors
     * @return bool True if valid
     */
    public static function text($text, $minLength = 1, $maxLength = 5000, $fieldName = 'Text') {
        if (!self::length($text, $minLength, $maxLength, $fieldName)) {
            return false;
        }
        
        // Check for excessive repeated characters
        if (preg_match('/(.)\1{10,}/', $text)) {
            self::$errors[] = "$fieldName contains too many repeated characters";
            return false;
        }
        
        // Check for excessive punctuation
        $punctuationCount = preg_match_all('/[!@#$%^&*()_+=\[\]{}|;:,.<>?]/', $text);
        if ($punctuationCount > strlen($text) * 0.3) {
            self::$errors[] = "$fieldName contains excessive punctuation";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return bool True if valid
     */
    public static function fileUpload($file, $allowedTypes = [], $maxSize = MAX_FILE_SIZE) {
        if (!isset($file['error'])) {
            self::$errors[] = "No file uploaded";
            return false;
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                self::$errors[] = "No file uploaded";
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                self::$errors[] = "File too large";
                return false;
            default:
                self::$errors[] = "File upload error";
                return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            self::$errors[] = "File size exceeds limit (" . formatFileSize($maxSize) . ")";
            return false;
        }
        
        // Check MIME type if specified
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                self::$errors[] = "File type not allowed";
                return false;
            }
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = array_merge(ALLOWED_IMAGE_EXTENSIONS, ALLOWED_DOCUMENT_EXTENSIONS);
        
        if (!in_array($extension, $allowedExtensions)) {
            self::$errors[] = "File extension not allowed";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @param string $sessionToken Expected token from session
     * @return bool True if valid
     */
    public static function csrfToken($token, $sessionToken = null) {
        if (empty($token)) {
            self::$errors[] = "Security token is required";
            return false;
        }
        
        $sessionToken = $sessionToken ?: ($_SESSION['csrf_token'] ?? '');
        
        if (empty($sessionToken)) {
            self::$errors[] = "No security token in session";
            return false;
        }
        
        if (!hash_equals($sessionToken, $token)) {
            self::$errors[] = "Invalid security token";
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize string input
     * 
     * @param string $input Input to sanitize
     * @param bool $stripTags Whether to strip HTML tags
     * @return string Sanitized input
     */
    public static function sanitize($input, $stripTags = true) {
        if (!is_string($input)) {
            return $input;
        }
        
        // Trim whitespace
        $input = trim($input);
        
        // Strip HTML tags if requested
        if ($stripTags) {
            $input = strip_tags($input);
        }
        
        // Encode HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Sanitize array recursively
     * 
     * @param array $array Array to sanitize
     * @param bool $stripTags Whether to strip HTML tags
     * @return array Sanitized array
     */
    public static function sanitizeArray($array, $stripTags = true) {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            $key = self::sanitize($key, true);
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $stripTags);
            } else {
                $sanitized[$key] = self::sanitize($value, $stripTags);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate JSON data
     * 
     * @param string $json JSON string to validate
     * @return bool True if valid JSON
     */
    public static function json($json) {
        if (empty($json)) {
            self::$errors[] = "JSON data is required";
            return false;
        }
        
        json_decode($json);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$errors[] = "Invalid JSON format: " . json_last_error_msg();
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate pagination parameters
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Validated pagination parameters
     */
    public static function pagination($page = 1, $perPage = null) {
        $page = max(1, (int)$page);
        $perPage = $perPage ?: DEFAULT_ITEMS_PER_PAGE;
        $perPage = max(1, min(MAX_ITEMS_PER_PAGE, (int)$perPage));
        
        return ['page' => $page, 'per_page' => $perPage];
    }
    
    /**
     * Validate search query
     * 
     * @param string $query Search query
     * @param int $minLength Minimum query length
     * @param int $maxLength Maximum query length
     * @return bool True if valid
     */
    public static function searchQuery($query, $minLength = 2, $maxLength = 100) {
        if (empty($query)) {
            return true; // Empty search is allowed
        }
        
        if (!self::length($query, $minLength, $maxLength, "Search query")) {
            return false;
        }
        
        // Check for SQL injection patterns (basic check)
        $sqlPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
            '/[\'";]/',
            '/--/',
            '/\/\*.*\*\//'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                self::$errors[] = "Search query contains invalid characters";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate multiple values (for checkboxes, multi-select, etc.)
     * 
     * @param array $values Values to validate
     * @param array $allowedValues Allowed values
     * @param int $minCount Minimum number of selections
     * @param int $maxCount Maximum number of selections
     * @return bool True if valid
     */
    public static function multipleValues($values, $allowedValues, $minCount = 0, $maxCount = null) {
        if (!is_array($values)) {
            self::$errors[] = "Multiple values must be provided as an array";
            return false;
        }
        
        $count = count($values);
        
        if ($count < $minCount) {
            self::$errors[] = "At least $minCount selections required";
            return false;
        }
        
        if ($maxCount !== null && $count > $maxCount) {
            self::$errors[] = "Maximum $maxCount selections allowed";
            return false;
        }
        
        // Check if all values are allowed
        $invalid = array_diff($values, $allowedValues);
        if (!empty($invalid)) {
            self::$errors[] = "Invalid selections: " . implode(', ', $invalid);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get validation errors
     * 
     * @return array Array of validation errors
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Check if there are validation errors
     * 
     * @return bool True if there are errors
     */
    public static function hasErrors() {
        return !empty(self::$errors);
    }
    
    /**
     * Clear validation errors
     */
    public static function clearErrors() {
        self::$errors = [];
    }
    
    /**
     * Get first validation error
     * 
     * @return string|null First error message or null
     */
    public static function getFirstError() {
        return self::$errors[0] ?? null;
    }
    
    /**
     * Add custom error
     * 
     * @param string $error Error message
     */
    public static function addError($error) {
        self::$errors[] = $error;
    }
    
    /**
     * Validate complex project data
     * 
     * @param array $data Project data
     * @return bool True if all validation passes
     */
    public static function validateProjectData($data) {
        $valid = true;
        
        // Required fields
        if (!self::required($data, ['name', 'description', 'profile_name', 'password'])) {
            $valid = false;
        }
        
        // Project name
        if (isset($data['name']) && !self::projectName($data['name'])) {
            $valid = false;
        }
        
        // Description
        if (isset($data['description']) && !self::text($data['description'], 10, 5000, 'Description')) {
            $valid = false;
        }
        
        // Profile name (username)
        if (isset($data['profile_name']) && !self::username($data['profile_name'])) {
            $valid = false;
        }
        
        // Password
        if (isset($data['password']) && !self::password($data['password'])) {
            $valid = false;
        }
        
        // Optional email
        if (isset($data['email']) && !empty($data['email']) && !self::email($data['email'], false)) {
            $valid = false;
        }
        
        // Optional website
        if (isset($data['website']) && !empty($data['website']) && !self::url($data['website'], false)) {
            $valid = false;
        }
        
        // Date
        if (isset($data['date']) && !self::date($data['date'])) {
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Validate innovator data
     * 
     * @param array $data Innovator data
     * @return bool True if valid
     */
    public static function validateInnovatorData($data) {
        $valid = true;
        
        // Required fields
        if (!self::required($data, ['name', 'email', 'role'])) {
            $valid = false;
        }
        
        // Name
        if (isset($data['name']) && !self::name($data['name'])) {
            $valid = false;
        }
        
        // Email
        if (isset($data['email']) && !self::email($data['email'])) {
            $valid = false;
        }
        
        // Role
        if (isset($data['role']) && !self::text($data['role'], 2, 100, 'Role')) {
            $valid = false;
        }
        
        // Optional experience level
        if (isset($data['experience_level']) && !empty($data['experience_level'])) {
            if (!self::text($data['experience_level'], 1, 100, 'Experience level')) {
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate mentor data
     * 
     * @param array $data Mentor data
     * @return bool True if valid
     */
    public static function validateMentorData($data) {
        $valid = true;
        
        // Required fields
        if (!self::required($data, ['name', 'email', 'password'])) {
            $valid = false;
        }
        
        // Name
        if (isset($data['name']) && !self::name($data['name'])) {
            $valid = false;
        }
        
        // Email
        if (isset($data['email']) && !self::email($data['email'])) {
            $valid = false;
        }
        
        // Password
        if (isset($data['password']) && !self::password($data['password'])) {
            $valid = false;
        }
        
        // Optional bio
        if (isset($data['bio']) && !empty($data['bio'])) {
            if (!self::text($data['bio'], 10, 1000, 'Bio')) {
                $valid = false;
            }
        }
        
        // Optional expertise
        if (isset($data['expertise']) && !empty($data['expertise'])) {
            if (!self::text($data['expertise'], 2, 200, 'Expertise')) {
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate rating data
     * 
     * @param array $data Rating data
     * @return bool True if valid
     */
    public static function validateRatingData($data) {
        $valid = true;
        
        // Required fields
        if (!self::required($data, ['stage', 'percentage'])) {
            $valid = false;
        }
        
        // Stage
        if (isset($data['stage']) && !self::projectStage($data['stage'])) {
            $valid = false;
        }
        
        // Percentage
        if (isset($data['percentage']) && !self::percentage($data['percentage'])) {
            $valid = false;
        }
        
        // Optional notes
        if (isset($data['notes']) && !empty($data['notes'])) {
            if (!self::text($data['notes'], 5, 1000, 'Notes')) {
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate comment data
     * 
     * @param array $data Comment data
     * @return bool True if valid
     */
    public static function validateCommentData($data) {
        $valid = true;
        
        // Required fields
        if (!self::required($data, ['comment_text'])) {
            $valid = false;
        }
        
        // Comment text
        if (isset($data['comment_text']) && !self::text($data['comment_text'], COMMENT_MIN_LENGTH, COMMENT_MAX_LENGTH, 'Comment')) {
            $valid = false;
        }
        
        // Optional parent ID (for replies)
        if (isset($data['parent_id']) && !empty($data['parent_id'])) {
            if (!self::intRange($data['parent_id'], 1, null, 'Parent comment ID')) {
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Clean and prepare data for database insertion
     * 
     * @param array $data Data to clean
     * @param array $allowedFields Allowed field names
     * @return array Cleaned data
     */
    public static function cleanData($data, $allowedFields = []) {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            // Skip if field not allowed
            if (!empty($allowedFields) && !in_array($key, $allowedFields)) {
                continue;
            }
            
            // Clean the value
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }
            
            $cleaned[$key] = $value;
        }
        
        return $cleaned;
    }
}