<?php
// api/system/test-email.php - Test Email Configuration (Admin Only)
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
    if (empty($input['email'])) {
        throw new Exception('Email address is required');
    }

    $testEmail = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$testEmail) {
        throw new Exception('Invalid email address');
    }

    $adminId = $auth->getUserId();

    // Prepare test email content
    $subject = 'Test Email - JHUB AFRICA System';
    $message = "This is a test email from JHUB AFRICA Project Tracker.\n\n";
    $message .= "If you received this email, your SMTP configuration is working correctly!\n\n";
    $message .= "Test Details:\n";
    $message .= "- Sent at: " . date('Y-m-d H:i:s') . "\n";
    $message .= "- Sent by: Admin ID {$adminId}\n";
    $message .= "- System: " . SITE_NAME . "\n";
    $message .= "- Version: " . (defined('SITE_VERSION') ? SITE_VERSION : '1.0.0') . "\n\n";
    $message .= "This is an automated test message. Please do not reply.\n\n";
    $message .= "Best regards,\n";
    $message .= "JHUB AFRICA System";

    // Queue email notification
    $emailId = sendEmailNotification(
        $testEmail,
        $subject,
        $message,
        'system_test'
    );

    if ($emailId) {
        // Log activity
        logActivity(
            'admin',
            $adminId,
            'test_email_sent',
            "Sent test email to {$testEmail}",
            null,
            ['recipient' => $testEmail]
        );

        echo json_encode([
            'success' => true,
            'message' => "Test email has been queued and will be sent to {$testEmail}. Please check the inbox (and spam folder)."
        ]);
    } else {
        throw new Exception('Failed to queue test email');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>