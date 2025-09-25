<?php
// classes/Validator.php
// Input Validation System

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }
    
    // Validate required field
    public function required($field, $message = null) {
        $message = $message ?? "The {$field} field is required.";
        
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    // Validate email
    public function email($field, $message = null) {
        $message = $message ?? "The {$field} field must be a valid email address.";
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    // Validate minimum length
    public function min($field, $length, $message = null) {
        $message = $message ?? "The {$field} field must be at least {$length} characters.";
        
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) < $length) {
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    // Validate maximum length
    public function max($field, $length, $message = null) {
        $message = $message ?? "The {$field} field must not exceed {$length} characters.";
        
        if (isset($this->data[$field]) && strlen(trim($this->data[$field])) > $length) {
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    // Validate URL
    public function url($field, $message = null) {
        $message = $message ?? "The {$field} field must be a valid URL.";
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    // Validate numeric value
    public function numeric($field, $message = null) {
        $message = $message ?? "The {$field} field must be numeric.";
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    // Validate date
    public function date($field, $format = 'Y-m-d', $message = null) {
        $message = $message ?? "The {$field} field must be a valid date.";
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message;
            }
        }
        
        return $this;
    }
    
    // Validate file upload
    public function file($field, $extensions = [], $maxSize = null, $message = null) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }
        
        $file = $_FILES[$field];
        $maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field][] = "File upload failed. Please try again.";
            return $this;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $this->errors[$field][] = "File size must not exceed {$maxSizeMB}MB.";
        }
        
        // Check file extension
        if (!empty($extensions)) {
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $extensions)) {
                $allowedExts = implode(', ', $extensions);
                $this->errors[$field][] = "File must be one of: {$allowedExts}";
            }
        }
        
        return $this;
    }
    
    // Check if validation passed
    public function isValid() {
        return empty($this->errors);
    }
    
    // Get all errors
    public function getErrors() {
        return $this->errors;
    }
    
    // Get errors for specific field
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : [];
    }
    
    // Get first error for field
    public function getFirstError($field) {
        $errors = $this->getError($field);
        return !empty($errors) ? $errors[0] : null;
    }
    
    // Sanitize input
    public static function sanitize($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Generate CSRF token HTML input
    public static function csrfInput() {
        $auth = Auth::getInstance();
        $token = $auth->generateCSRFToken();
        return "<input type='hidden' name='csrf_token' value='{$token}'>";
    }
    
    // Validate CSRF token from POST data
    public static function validateCSRF() {
        $auth = Auth::getInstance();
        $token = $_POST['csrf_token'] ?? '';
        return $auth->validateCSRFToken($token);
    }
}

?>