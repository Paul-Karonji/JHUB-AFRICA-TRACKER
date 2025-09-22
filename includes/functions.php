<?php
/**
 * Global utility functions shared across the application.
 */

if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Send standardized JSON response and terminate script.
 */
function jsonResponse($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    if (is_array($payload)) {
        $payload = array_merge($payload, [
            'timestamp' => time(),
            'api_version' => API_VERSION
        ]);
    }
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Safely fetch JSON body from request.
 */
function getJsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Merge request payload from JSON and POST arrays.
 */
function getRequestPayload() {
    return array_merge(getJsonBody(), $_POST ?? []);
}

/**
 * Quickly validate required keys exist in associative array.
 */
function requireKeys(array $source, array $keys) {
    $missing = [];
    foreach ($keys as $key) {
        if (!isset($source[$key]) || $source[$key] === '') {
            $missing[] = $key;
        }
    }
    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }
}

/**
 * Sanitize string for safe output.
 */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Convert associative array keys to snake_case recursively.
 */
function arrayKeysToSnakeCase(array $data) {
    $result = [];
    foreach ($data as $key => $value) {
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));
        $result[$snake] = is_array($value) ? arrayKeysToSnakeCase($value) : $value;
    }
    return $result;
}

/**
 * Simple pagination helper.
 */
function buildPagination($total, $page, $perPage) {
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    return [
        'total' => (int) $total,
        'page' => max(1, (int) $page),
        'per_page' => (int) $perPage,
        'total_pages' => max(1, $totalPages)
    ];
}

/**
 * Ensure request method matches expected.
 */
function requireMethod($method) {
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== strtoupper($method)) {
        jsonResponse([
            'success' => false,
            'error' => 'Method not allowed',
            'allowed_method' => strtoupper($method)
        ], 405);
    }
}

/**
 * Ensure rate limit checks for custom endpoints (simple token bucket in session).
 */
function rateLimit($key, $maxAttempts = 100, $windowSeconds = 3600) {
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [];
    }

    $now = time();
    $_SESSION['rate_limits'][$key] = array_filter(
        $_SESSION['rate_limits'][$key],
        function ($attempt) use ($now, $windowSeconds) {
            return ($now - $attempt) < $windowSeconds;
        }
    );

    if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
        return false;
    }

    $_SESSION['rate_limits'][$key][] = $now;
    return true;
}

/**
 * Build domain absolute URL from relative path.
 */
function absoluteUrl($path) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = ltrim($path, '/');
    return $scheme . $host . '/' . $path;
}
?>
