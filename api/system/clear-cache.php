<?php
// api/system/clear-cache.php - Clear System Cache
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin authentication
if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }

    // Validate CSRF token
    if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    $adminId = $auth->getUserId();
    $clearedItems = [];

    // Clear PHP session files (optional - be careful with this)
    // Note: This will log out all users except the current admin
    if (isset($input['clear_sessions']) && $input['clear_sessions']) {
        $sessionPath = session_save_path();
        if (!empty($sessionPath) && is_dir($sessionPath)) {
            $sessionFiles = glob($sessionPath . '/sess_*');
            $currentSessionId = session_id();
            
            foreach ($sessionFiles as $file) {
                // Don't delete current admin's session
                if (strpos($file, $currentSessionId) === false) {
                    @unlink($file);
                }
            }
            $clearedItems[] = 'PHP sessions';
        }
    }

    // Clear application cache directory if it exists
    $cacheDir = dirname(dirname(__DIR__)) . '/cache';
    if (file_exists($cacheDir) && is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $clearedItems[] = 'Application cache files';
    }

    // Clear temporary upload files older than 24 hours
    $tempDir = dirname(dirname(__DIR__)) . '/assets/uploads/temp';
    if (file_exists($tempDir) && is_dir($tempDir)) {
        $files = glob($tempDir . '/*');
        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 86400) {
                if (@unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        if ($deletedCount > 0) {
            $clearedItems[] = "Temporary files ({$deletedCount} files)";
        }
    }

    // Clear old thumbnail cache if exists
    $thumbDir = dirname(dirname(__DIR__)) . '/assets/uploads/thumbnails';
    if (file_exists($thumbDir) && is_dir($thumbDir)) {
        $files = glob($thumbDir . '/*');
        $deletedCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (@unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        if ($deletedCount > 0) {
            $clearedItems[] = "Thumbnail cache ({$deletedCount} files)";
        }
    }

    // Clear opcode cache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $clearedItems[] = 'PHP OpCache';
    }

    // Clear APCu cache if available
    if (function_exists('apcu_clear_cache')) {
        apcu_clear_cache();
        $clearedItems[] = 'APCu cache';
    }

    if (empty($clearedItems)) {
        $clearedItems[] = 'No cache items found to clear';
    }

    // Log this maintenance activity
    logActivity(
        'admin',
        $adminId,
        'cache_cleared',
        'Cleared system cache',
        null,
        ['cleared_items' => $clearedItems]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'cleared_items' => $clearedItems
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>