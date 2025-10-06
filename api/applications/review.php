<?php
// api/applications/review.php
// Admin Application Review API - FIXED VERSION (Presentations are NEVER deleted)

header('Content-Type: application/json');
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }

    if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    if (empty($input['action']) || empty($input['application_id'])) {
        throw new Exception('Missing required fields');
    }

    $action = $input['action'];
    $applicationId = intval($input['application_id']);
    $adminMessage = isset($input['admin_message']) ? trim($input['admin_message']) : '';
    $rejectionReason = isset($input['rejection_reason']) ? trim($input['rejection_reason']) : '';

    $application = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [$applicationId]
    );

    if (!$application) {
        throw new Exception('Application not found');
    }

    if ($application['status'] !== 'pending') {
        throw new Exception('This application has already been processed');
    }

    $database->beginTransaction();

    try {
        if ($action === 'approve') {
            // Update application status
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

            // Create project from application
            $projectData = [
                'project_name' => $application['project_name'],
                'date' => $application['date'],
                'project_email' => $application['project_email'],
                'project_website' => $application['project_website'],
                'description' => $application['description'],
                'profile_name' => $application['profile_name'],
                'password' => $application['password'],
                'project_lead_name' => $application['project_lead_name'],
                'project_lead_email' => $application['project_lead_email'],
                'current_stage' => 1,
                'status' => 'active',
                'created_from_application' => $applicationId,
                'created_by_admin' => $auth->getUserId()
            ];

            $projectId = $database->insert('projects', $projectData);

            if (!$projectId) {
                throw new Exception('Failed to create project');
            }

            // REMOVED: File deletion scheduling - presentations are now kept permanently

            // Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_approved',
                "Approved application #{$applicationId} and created project #{$projectId}",
                $projectId,
                ['application_id' => $applicationId, 'project_name' => $application['project_name']]
            );

            // Send approval email with admin message
            $loginUrl = SITE_URL . '/auth/project-login.php';
            
            $emailContent = "<p>Dear {$application['project_lead_name']},</p>";
            $emailContent .= "<p><strong>Congratulations!</strong> &#127881;</p>";
            $emailContent .= "<p>Your project application for <strong>{$application['project_name']}</strong> has been approved!</p>";
            
            if ($adminMessage) {
                $emailContent .= "<div style='background: #d5d9eb; padding: 15px; border-left: 4px solid #2c409a; margin: 20px 0;'>";
                $emailContent .= "<p><strong>Message from Admin:</strong></p>";
                $emailContent .= "<p>" . nl2br(htmlspecialchars($adminMessage)) . "</p>";
                $emailContent .= "</div>";
            }
            
            $emailContent .= "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #2c409a; margin: 20px 0;'>";
            $emailContent .= "<p><strong>Your Login Credentials:</strong></p>";
            $emailContent .= "<p><strong>Username:</strong> {$application['profile_name']}<br>";
            $emailContent .= "<strong>Login URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a></p>";
            $emailContent .= "</div>";
            $emailContent .= "<p>Your project is now active at <strong>Stage 1: Project Creation</strong>.</p>";
            $emailContent .= "<p>Your presentation file will remain securely stored in our system for future reference.</p>";
            $emailContent .= "<p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>";

            sendEmailNotification(
                $application['project_lead_email'],
                'Application Approved - Welcome to JHUB AFRICA!',
                $emailContent,
                'application_approved',
                array_merge($application, ['project_id' => $projectId, 'admin_message' => $adminMessage])
            );

            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application approved successfully! Project created and notification sent.',
                'project_id' => $projectId
            ]);

        } elseif ($action === 'reject') {
            
            if (empty($rejectionReason)) {
                throw new Exception('Rejection reason is required');
            }

            // Update application status with rejection reason and admin message
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

            // REMOVED: File deletion scheduling - presentations are now kept permanently

            // Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_rejected',
                "Rejected application #{$applicationId}: {$application['project_name']}",
                null,
                ['reason' => $rejectionReason]
            );

            // Send rejection email with admin message
            $emailContent = "<p>Dear {$application['project_lead_name']},</p>";
            $emailContent .= "<p>Thank you for your interest in the JHUB AFRICA program and for submitting your project application for <strong>{$application['project_name']}</strong>.</p>";
            $emailContent .= "<p>After careful review, we regret to inform you that your application has not been approved at this time.</p>";
            
            $emailContent .= "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
            $emailContent .= "<p><strong>Reason for Rejection:</strong></p>";
            $emailContent .= "<p>" . nl2br(htmlspecialchars($rejectionReason)) . "</p>";
            $emailContent .= "</div>";
            
            if ($adminMessage) {
                $emailContent .= "<div style='background: #d5d9eb; padding: 15px; border-left: 4px solid #2c409a; margin: 20px 0;'>";
                $emailContent .= "<p><strong>Additional Message from Admin:</strong></p>";
                $emailContent .= "<p>" . nl2br(htmlspecialchars($adminMessage)) . "</p>";
                $emailContent .= "</div>";
            }
            
            $emailContent .= "<p>We encourage you to:</p>";
            $emailContent .= "<ul>";
            $emailContent .= "<li>Review and refine your project based on this feedback</li>";
            $emailContent .= "<li>Strengthen your business model and market validation</li>";
            $emailContent .= "<li>Consider reapplying in the future with an improved proposal</li>";
            $emailContent .= "</ul>";
            $emailContent .= "<p>Your presentation file will remain in our system for your future reference.</p>";
            $emailContent .= "<p>If you have questions about this decision, please contact us at applications@jhubafrica.com</p>";
            $emailContent .= "<p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>";

            sendEmailNotification(
                $application['project_lead_email'],
                'Update on Your JHUB AFRICA Application',
                $emailContent,
                'application_rejected',
                array_merge($application, ['rejection_reason' => $rejectionReason, 'admin_message' => $adminMessage])
            );

            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application rejected. Notification email sent.'
            ]);

        } else {
            throw new Exception('Invalid action. Must be "approve" or "reject"');
        }

    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Application review error: ' . $e->getMessage());
}
?>