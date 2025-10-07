<?php
// api/mentors/toggle-status.php - Activate/Deactivate Mentor (Admin Only)
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
    if (!isset($input['mentor_id']) || !isset($input['is_active'])) {
        throw new Exception('Mentor ID and status are required');
    }

    $mentorId = intval($input['mentor_id']);
    $isActive = intval($input['is_active']); // 0 or 1
    $adminId = $auth->getUserId();

    // Validate is_active value
    if (!in_array($isActive, [0, 1])) {
        throw new Exception('Invalid status value');
    }

    // Get mentor info
    $mentor = $database->getRow(
        "SELECT * FROM mentors WHERE mentor_id = ?",
        [$mentorId]
    );

    if (!$mentor) {
        throw new Exception('Mentor not found');
    }

    // Check if status is already set
    if ($mentor['is_active'] == $isActive) {
        throw new Exception('Mentor is already ' . ($isActive ? 'active' : 'inactive'));
    }

    // Update mentor status
    $updateResult = $database->update(
        'mentors',
        ['is_active' => $isActive],
        'mentor_id = ?',
        [$mentorId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to update mentor status');
    }

    $action = $isActive ? 'activated' : 'deactivated';
    $statusText = $isActive ? 'active' : 'inactive';

    // Log activity
    logActivity(
        'admin',
        $adminId,
        'mentor_status_changed',
        "Mentor {$mentor['name']} has been {$action}",
        null,
        ['mentor_id' => $mentorId, 'new_status' => $statusText]
    );

    // Send notification email to mentor
    if ($isActive) {
        $message = "Dear {$mentor['name']},\n\n";
        $message .= "Your mentor account has been activated. You can now login to your dashboard and start mentoring projects.\n\n";
        $message .= "Login at: " . SITE_URL . "/auth/mentor-login.php\n\n";
        $message .= "Best regards,\n";
        $message .= "JHUB AFRICA Team";
    } else {
        $message = "Dear {$mentor['name']},\n\n";
        $message .= "Your mentor account has been temporarily deactivated.\n\n";
        $message .= "If you believe this was done in error or have any questions, please contact JHUB AFRICA support.\n\n";
        $message .= "Best regards,\n";
        $message .= "JHUB AFRICA Team";
    }

    sendEmailNotification(
        $mentor['email'],
        'Account Status Update - JHUB AFRICA',
        $message,
        'mentor_status_changed'
    );

    echo json_encode([
        'success' => true,
        'message' => "Mentor account has been {$action} successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>