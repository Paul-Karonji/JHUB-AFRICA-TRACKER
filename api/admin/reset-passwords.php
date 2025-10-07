<?php
// api/admins/reset-password.php - Reset Admin Password (Admin Only)
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
    if (empty($input['admin_id']) || empty($input['new_password'])) {
        throw new Exception('Admin ID and new password are required');
    }

    $targetAdminId = intval($input['admin_id']);
    $newPassword = $input['new_password'];
    $currentAdminId = $auth->getUserId();

    // Validate password strength
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Prevent admin from resetting their own password through this endpoint
    if ($targetAdminId === $currentAdminId) {
        throw new Exception('Please use the change password feature to update your own password');
    }

    // Get admin info
    $admin = $database->getRow(
        "SELECT * FROM admins WHERE admin_id = ?",
        [$targetAdminId]
    );

    if (!$admin) {
        throw new Exception('Admin not found');
    }

    // Hash new password
    $hashedPassword = Auth::hashPassword($newPassword);

    // Update admin password and reset login attempts
    $updateResult = $database->update(
        'admins',
        [
            'password' => $hashedPassword,
            'login_attempts' => 0,
            'locked_until' => null
        ],
        'admin_id = ?',
        [$targetAdminId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to reset password');
    }

    // Log activity
    logActivity(
        'admin',
        $currentAdminId,
        'admin_password_reset',
        "Reset password for admin: {$admin['username']}",
        null,
        ['target_admin_id' => $targetAdminId]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully. Please share the new password securely with the admin.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>