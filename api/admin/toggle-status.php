<?php
// api/admins/toggle-status.php - Activate/Deactivate Admin (Admin Only)
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

    // Validate required fields
    if (!isset($input['admin_id']) || !isset($input['is_active'])) {
        throw new Exception('Admin ID and status are required');
    }

    $targetAdminId = intval($input['admin_id']);
    $isActive = intval($input['is_active']); // 0 or 1
    $currentAdminId = $auth->getUserId();

    // Validate is_active value
    if (!in_array($isActive, [0, 1])) {
        throw new Exception('Invalid status value');
    }

    // Prevent admin from deactivating themselves
    if ($targetAdminId === $currentAdminId) {
        throw new Exception('You cannot change your own account status');
    }

    // Get admin info
    $admin = $database->getRow(
        "SELECT * FROM admins WHERE admin_id = ?",
        [$targetAdminId]
    );

    if (!$admin) {
        throw new Exception('Admin not found');
    }

    // Check if status is already set
    if ($admin['is_active'] == $isActive) {
        throw new Exception('Admin is already ' . ($isActive ? 'active' : 'inactive'));
    }

    // Update admin status
    $updateResult = $database->update(
        'admins',
        ['is_active' => $isActive],
        'admin_id = ?',
        [$targetAdminId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to update admin status');
    }

    $action = $isActive ? 'activated' : 'deactivated';
    $statusText = $isActive ? 'active' : 'inactive';

    // Log activity
    logActivity(
        'admin',
        $currentAdminId,
        'admin_status_changed',
        "Admin {$admin['username']} has been {$action}",
        null,
        ['target_admin_id' => $targetAdminId, 'new_status' => $statusText]
    );

    echo json_encode([
        'success' => true,
        'message' => "Admin account has been {$action} successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>