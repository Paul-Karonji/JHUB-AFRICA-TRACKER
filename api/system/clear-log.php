<?php
// api/system/clear-logs.php - Clear Old Activity Logs (Admin Only)
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

    // Get days parameter (default 90)
    $days = isset($input['days']) ? intval($input['days']) : 90;
    
    // Validate days
    if ($days < 30) {
        throw new Exception('Cannot delete logs newer than 30 days');
    }

    $adminId = $auth->getUserId();

    // Calculate cutoff date
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Count logs to be deleted
    $countToDelete = $database->count(
        'activity_logs',
        'created_at < ?',
        [$cutoffDate]
    );

    if ($countToDelete === 0) {
        throw new Exception('No logs found older than ' . $days . ' days');
    }

    // Delete old logs
    $deleteResult = $database->delete(
        'activity_logs',
        'created_at < ?',
        [$cutoffDate]
    );

    if (!$deleteResult) {
        throw new Exception('Failed to delete old logs');
    }

    // Log this maintenance activity
    logActivity(
        'admin',
        $adminId,
        'logs_cleared',
        "Cleared {$countToDelete} activity logs older than {$days} days",
        null,
        ['logs_deleted' => $countToDelete, 'cutoff_date' => $cutoffDate]
    );

    echo json_encode([
        'success' => true,
        'message' => "Successfully deleted {$countToDelete} activity logs older than {$days} days.",
        'logs_deleted' => $countToDelete
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>