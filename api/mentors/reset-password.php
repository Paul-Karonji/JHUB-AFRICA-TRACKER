<?php
// api/mentors/reset-password.php - Reset Mentor Password (Admin Only)
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
    if (empty($input['mentor_id']) || empty($input['new_password'])) {
        throw new Exception('Mentor ID and new password are required');
    }

    $mentorId = intval($input['mentor_id']);
    $newPassword = $input['new_password'];
    $adminId = $auth->getUserId();

    // Validate password strength
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Get mentor info
    $mentor = $database->getRow(
        "SELECT * FROM mentors WHERE mentor_id = ?",
        [$mentorId]
    );

    if (!$mentor) {
        throw new Exception('Mentor not found');
    }

    // Hash new password
    $hashedPassword = Auth::hashPassword($newPassword);

    // Update mentor password
    $updateResult = $database->update(
        'mentors',
        ['password' => $hashedPassword],
        'mentor_id = ?',
        [$mentorId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to reset password');
    }

    // Log activity
    logActivity(
        'admin',
        $adminId,
        'mentor_password_reset',
        "Reset password for mentor: {$mentor['name']}",
        null,
        ['mentor_id' => $mentorId]
    );

    // Send notification email to mentor
    $message = "Dear {$mentor['name']},\n\n";
    $message .= "Your password has been reset by an administrator.\n\n";
    $message .= "New Login Credentials:\n";
    $message .= "Email: {$mentor['email']}\n";
    $message .= "Password: {$newPassword}\n\n";
    $message .= "Please login and change your password immediately for security reasons.\n\n";
    $message .= "Login at: " . SITE_URL . "/auth/mentor-login.php\n\n";
    $message .= "If you did not request this password reset, please contact JHUB AFRICA support immediately.\n\n";
    $message .= "Best regards,\n";
    $message .= "JHUB AFRICA Team";

    sendEmailNotification(
        $mentor['email'],
        'Password Reset - JHUB AFRICA',
        $message,
        'password_reset'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully. Notification email has been sent to the mentor.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>