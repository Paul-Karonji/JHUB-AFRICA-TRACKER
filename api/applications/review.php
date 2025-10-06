<?php
/**
 * api/applications/review.php
 * Admin Application Review API - COMPLETE DEBUGGED VERSION
 * 
 * FIXES:
 * 1. Proper error handling with JSON responses
 * 2. Clean output buffering
 * 3. Detailed error logging
 * 4. Transaction safety
 */

// ========================================
// CRITICAL: Clean all output first
// ========================================
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, we'll return JSON

// Set JSON header immediately
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Custom error handler for JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // ========================================
    // Initialize Application
    // ========================================
    require_once '../../includes/init.php';

    // ========================================
    // Authentication Check
    // ========================================
    if (!$auth->isLoggedIn()) {
        throw new Exception('Authentication required');
    }

    if ($auth->getUserType() !== USER_TYPE_ADMIN) {
        throw new Exception('Admin access required');
    }

    // ========================================
    // Method Check
    // ========================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    // ========================================
    // Get and Validate Input
    // ========================================
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        throw new Exception('Empty request body');
    }

    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Log for debugging
    error_log('Review API Input: ' . print_r($input, true));

    // ========================================
    // CSRF Token Validation
    // ========================================
    if (!isset($input['csrf_token'])) {
        throw new Exception('CSRF token missing');
    }

    if (!$auth->validateCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid CSRF token - please refresh the page');
    }

    // ========================================
    // Field Validation
    // ========================================
    if (empty($input['action'])) {
        throw new Exception('Action is required');
    }

    if (empty($input['application_id'])) {
        throw new Exception('Application ID is required');
    }

    // Extract and sanitize data
    $action = trim($input['action']);
    $applicationId = intval($input['application_id']);
    $adminMessage = isset($input['admin_message']) ? trim($input['admin_message']) : '';
    $rejectionReason = isset($input['rejection_reason']) ? trim($input['rejection_reason']) : '';

    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action. Must be "approve" or "reject"');
    }

    // ========================================
    // Get Application
    // ========================================
    $application = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [$applicationId]
    );

    if (!$application) {
        throw new Exception('Application not found');
    }

    if ($application['status'] !== 'pending') {
        throw new Exception('This application has already been ' . $application['status']);
    }

    // ========================================
    // Process Based on Action
    // ========================================
    $database->beginTransaction();

    try {
        if ($action === 'approve') {
            // ========================================
            // APPROVE APPLICATION
            // ========================================
            
            // 1. Update application status
            $updateResult = $database->update(
                'project_applications',
                [
                    'status' => 'approved',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => $auth->getUserId(),
                    'admin_message' => $adminMessage
                ],
                'application_id = ?',
                [$applicationId]
            );

            if (!$updateResult) {
                throw new Exception('Failed to update application status');
            }

            // 2. Create new project
            $projectId = $database->insert('projects', [
                'project_name' => $application['project_name'],
                'date' => $application['date'], // REQUIRED field from application
                'description' => $application['description'],
                'project_email' => $application['project_email'],
                'project_website' => $application['project_website'],
                'profile_name' => $application['profile_name'],
                'password' => $application['password'], // Already hashed
                'project_lead_name' => $application['project_lead_name'], // REQUIRED
                'project_lead_email' => $application['project_lead_email'], // REQUIRED
                'current_stage' => 1,
                'status' => 'active',
                'created_from_application' => $applicationId,
                'created_by_admin' => $auth->getUserId(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$projectId) {
                throw new Exception('Failed to create project');
            }

            // Note: Project lead will add themselves as a team member when they first log in
            // No need to create project_innovators entry here

            // 3. Log activity
            $database->insert('activity_logs', [
                'user_type' => USER_TYPE_ADMIN,
                'user_id' => $auth->getUserId(),
                'action' => 'application_approved',
                'description' => "Approved application for project: {$application['project_name']}",
                'additional_data' => json_encode([
                    'application_id' => $applicationId,
                    'project_id' => $projectId
                ])
            ]);

            // 4. Build and send approval email
            $emailContent = buildApprovalEmail($application, $adminMessage);

            try {
                sendEmailNotification(
                    $application['project_lead_email'],
                    'Application Approved - Welcome to JHUB AFRICA!',
                    $emailContent,
                    'application_approved',
                    []
                );
            } catch (Exception $e) {
                // Log email error but don't fail the approval
                error_log('Email send failed: ' . $e->getMessage());
            }

            // Commit transaction
            $database->commit();

            // Clear buffer and send success response
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Application approved successfully! Project created and notification sent.',
                'project_id' => $projectId
            ]);

        } elseif ($action === 'reject') {
            // ========================================
            // REJECT APPLICATION
            // ========================================
            
            if (empty($rejectionReason)) {
                throw new Exception('Rejection reason is required');
            }

            // 1. Update application status
            $updateResult = $database->update(
                'project_applications',
                [
                    'status' => 'rejected',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => $auth->getUserId(),
                    'rejection_reason' => $rejectionReason,
                    'admin_message' => $adminMessage
                ],
                'application_id = ?',
                [$applicationId]
            );

            if (!$updateResult) {
                throw new Exception('Failed to update application status');
            }

            // 2. Log activity
            $database->insert('activity_logs', [
                'user_type' => USER_TYPE_ADMIN,
                'user_id' => $auth->getUserId(),
                'action' => 'application_rejected',
                'description' => "Rejected application for project: {$application['project_name']}",
                'additional_data' => json_encode([
                    'application_id' => $applicationId,
                    'reason' => $rejectionReason
                ])
            ]);

            // 3. Build and send rejection email
            $emailContent = buildRejectionEmail($application, $rejectionReason, $adminMessage);

            try {
                sendEmailNotification(
                    $application['project_lead_email'],
                    'Update on Your JHUB AFRICA Application',
                    $emailContent,
                    'application_rejected',
                    []
                );
            } catch (Exception $e) {
                // Log email error but don't fail the rejection
                error_log('Email send failed: ' . $e->getMessage());
            }

            // Commit transaction
            $database->commit();

            // Clear buffer and send success response
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Application rejected. Notification email sent.'
            ]);
        }

    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Clean output buffer
    ob_clean();
    
    // Set appropriate HTTP code
    $httpCode = 400;
    if (strpos($e->getMessage(), 'Authentication') !== false) {
        $httpCode = 401;
    } elseif (strpos($e->getMessage(), 'Admin access') !== false) {
        $httpCode = 403;
    }
    http_response_code($httpCode);

    // Log error
    error_log('Application Review Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Return JSON error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => DEBUG_MODE ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}

// Final output flush
ob_end_flush();

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Build approval email HTML
 */
function buildApprovalEmail($application, $adminMessage) {
    $content = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4caf50; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .message-box { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0; }
            .credentials { background: white; padding: 15px; border: 1px solid #ddd; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Congratulations!</h1>
                <p>Your Application Has Been Approved</p>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($application['project_lead_name']) . ',</p>
                
                <p>We are excited to inform you that your project application for <strong>' . htmlspecialchars($application['project_name']) . '</strong> has been approved!</p>';
    
    if (!empty($adminMessage)) {
        $content .= '
                <div class="message-box">
                    <p><strong>Message from Admin:</strong></p>
                    <p>' . nl2br(htmlspecialchars($adminMessage)) . '</p>
                </div>';
    }
    
    $content .= '
                <div class="credentials">
                    <h3>Your Login Credentials:</h3>
                    <ul>
                        <li><strong>Username:</strong> ' . htmlspecialchars($application['profile_name']) . '</li>
                        <li><strong>Login URL:</strong> ' . SITE_URL . '/auth/login.php</li>
                    </ul>
                    <p><strong>Note:</strong> Use the password you created during application.</p>
                </div>
                
                <h3>Next Steps:</h3>
                <ol>
                    <li>Log in to your project dashboard</li>
                    <li>Complete your project profile</li>
                    <li>Start documenting your innovation journey</li>
                    <li>Connect with mentors and resources</li>
                </ol>
                
                <p>Welcome to JHUB AFRICA!</p>
                <p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' JHUB AFRICA. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $content;
}

/**
 * Build rejection email HTML
 */
function buildRejectionEmail($application, $rejectionReason, $adminMessage) {
    $content = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .reason-box { background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 20px 0; }
            .message-box { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Application Update</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($application['project_lead_name']) . ',</p>
                
                <p>Thank you for your interest in JHUB AFRICA and for submitting your project application for <strong>' . htmlspecialchars($application['project_name']) . '</strong>.</p>
                
                <p>After careful review, we regret to inform you that we are unable to approve your application at this time.</p>
                
                <div class="reason-box">
                    <p><strong>Reason for Decision:</strong></p>
                    <p>' . nl2br(htmlspecialchars($rejectionReason)) . '</p>
                </div>';
    
    if (!empty($adminMessage)) {
        $content .= '
                <div class="message-box">
                    <p><strong>Additional Message from Admin:</strong></p>
                    <p>' . nl2br(htmlspecialchars($adminMessage)) . '</p>
                </div>';
    }
    
    $content .= '
                <p>We encourage you to:</p>
                <ul>
                    <li>Review and refine your project based on this feedback</li>
                    <li>Strengthen your business model and market validation</li>
                    <li>Consider reapplying in the future with an improved proposal</li>
                </ul>
                
                <p>If you have questions about this decision, please contact us at applications@jhubafrica.com</p>
                
                <p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' JHUB AFRICA. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $content;
}
?>