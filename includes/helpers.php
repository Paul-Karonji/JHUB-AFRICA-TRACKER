<?php
// includes/helpers.php
// Helper Functions

/**
 * Redirect to a URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently");
    }
    
    // Handle relative URLs
    if (strpos($url, 'http') !== 0) {
        $url = SITE_URL . $url;
    }
    
    header("Location: {$url}");
    exit;
}

/**
 * Flash message system
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_messages'][] = ['message' => $message, 'type' => $type];
}

function getFlashMessages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

function displayFlashMessages() {
    $messages = getFlashMessages();
    if (empty($messages)) return '';
    
    $html = '';
    foreach ($messages as $flash) {
        $alertClass = 'alert-info';
        switch ($flash['type']) {
            case 'success': $alertClass = 'alert-success'; break;
            case 'error': $alertClass = 'alert-danger'; break;
            case 'warning': $alertClass = 'alert-warning'; break;
        }
        
        $html .= "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
        $html .= htmlspecialchars($flash['message']);
        $html .= "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>";
        $html .= "<span aria-hidden='true'>&times;</span>";
        $html .= "</button></div>";
    }
    
    return $html;
}

/**
 * Format date for display
 */
function formatDate($date, $format = null) {
    if (empty($date)) return '';
    
    $format = $format ?? DISPLAY_DATE_FORMAT;
    
    if ($date instanceof DateTime) {
        return $date->format($format);
    }
    
    return date($format, strtotime($date));
}

/**
 * Format date as "time ago"
 */
function timeAgo($date) {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    
    return floor($diff / 31536000) . ' years ago';
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

/**
 * Check if string is valid JSON
 */
function isValidJSON($str) {
    json_decode($str);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Escape HTML output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $path;
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    return $protocol . '://' . $host . BASE_PATH;
}

/**
 * Include template with variables
 */
function includeTemplate($template, $variables = []) {
    extract($variables);
    include __DIR__ . "/../templates/{$template}.php";
}

/**
 * Load partial with variables
 */
function loadPartial($partial, $variables = []) {
    extract($variables);
    include __DIR__ . "/../partials/{$partial}.php";
}

/**
 * Get gravatar URL
 */
function getGravatar($email, $size = 100, $default = 'identicon') {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
}

/**
 * Pagination helper
 */
function paginate($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevUrl = $baseUrl . '?page=' . ($currentPage - 1);
        $html .= "<li class='page-item'><a class='page-link' href='{$prevUrl}'>Previous</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Previous</span></li>";
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page=1'>1</a></li>";
        if ($start > 2) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= "<li class='page-item active'><span class='page-link'>{$i}</span></li>";
        } else {
            $pageUrl = $baseUrl . '?page=' . $i;
            $html .= "<li class='page-item'><a class='page-link' href='{$pageUrl}'>{$i}</a></li>";
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $lastUrl = $baseUrl . '?page=' . $totalPages;
        $html .= "<li class='page-item'><a class='page-link' href='{$lastUrl}'>{$totalPages}</a></li>";
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextUrl = $baseUrl . '?page=' . ($currentPage + 1);
        $html .= "<li class='page-item'><a class='page-link' href='{$nextUrl}'>Next</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Next</span></li>";
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

?>