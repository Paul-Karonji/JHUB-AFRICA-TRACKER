<?php
// api/applications/review.php
// Admin Application Review API - FIXED VERSION

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin authentication
$auth->requireUserType(USER_TYPE_ADMIN);

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
    if (empty($input['action']) || empty($input['application_id'])) {
        throw new Exception('Missing required fields');
    }

    $action = $input['action']; // 'approve' or 'reject'
    $applicationId = intval($input['application_id']);
    $rejectionReason = isset($input['rejection_reason']) ? trim($input['rejection_reason']) : '';

    // Get application
    $application = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [$applicationId]
    );

    if (!$application) {
        throw new Exception('Application not found');
    }

    // Check if already processed
    if ($application['status'] !== 'pending') {
        throw new Exception('This application has already been processed');
    }

    // Start transaction
    $database->beginTransaction();

    try {
        if ($action === 'approve') {
            // STEP 1: Update application status to approved
            $updateResult = $database->update(
                'project_applications',
                [
                    'status' => 'approved',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => $auth->getUserId()
                ],
                'application_id = ?',
                [$applicationId]
            );

            if (!$updateResult) {
                throw new Exception('Failed to update application status');
            }

            // STEP 2: Create project from application
            $projectData = [
                'project_name' => $application['project_name'],
                'date' => $application['date'],
                'project_email' => $application['project_email'],
                'project_website' => $application['project_website'],
                'description' => $application['description'],
                'profile_name' => $application['profile_name'],
                'password' => $application['password'], // Already hashed
                'project_lead_name' => $application['project_lead_name'],
                'project_lead_email' => $application['project_lead_email'],
                'current_stage' => 1, // Stage 1: Project Creation
                'status' => 'active',
                'created_from_application' => $applicationId,
                'created_by_admin' => $auth->getUserId()
            ];

            $projectId = $database->insert('projects', $projectData);

            if (!$projectId) {
                throw new Exception('Failed to create project');
            }

            // STEP 3: Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_approved',
                "Approved application #{$applicationId} and created project #{$projectId}",
                $projectId,
                ['application_id' => $applicationId, 'project_name' => $application['project_name']]
            );

            // STEP 4: Send approval email
            $loginUrl = SITE_URL . '/auth/project-login.php';
            
            $emailContent = "<p>Dear {$application['project_lead_name']},</p>";
            $emailContent .= "<p><strong>Congratulations!</strong> 🎉</p>";
            $emailContent .= "<p>Your project application for <strong>{$application['project_name']}</strong> has been approved!</p>";
            $emailContent .= "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;'>";
            $emailContent .= "<p><strong>Your Login Credentials:</strong></p>";
            $emailContent .= "<p><strong>Username:</strong> {$application['profile_name']}<br>";
            $emailContent .= "<strong>Login URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a></p>";
            $emailContent .= "</div>";
            $emailContent .= "<p>Your project is now active at <strong>Stage 1: Project Creation</strong>. You can now:</p>";
            $emailContent .= "<ul>";
            $emailContent .= "<li>Access your project dashboard</li>";
            $emailContent .= "<li>Build your team by adding innovators</li>";
            $emailContent .= "<li>Connect with expert mentors</li>";
            $emailContent .= "<li>Access resources and learning materials</li>";
            $emailContent .= "</ul>";
            $emailContent .= "<p>Welcome to the JHUB AFRICA innovation ecosystem!</p>";
            $emailContent .= "<p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>";

            sendEmailNotification(
                $application['project_lead_email'],
                'Your JHUB AFRICA Application Has Been Approved!',
                $emailContent,
                NOTIFY_APPLICATION_APPROVED,
                array_merge($application, ['project_id' => $projectId])
            );

            // Commit transaction
            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application approved successfully! Project has been created and the applicant has been notified.',
                'project_id' => $projectId
            ]);

        } elseif ($action === 'reject') {
            // Reject application
            
            if (empty($rejectionReason)) {
                throw new Exception('Rejection reason is required');
            }

            // Update application status
            $updateResult = $database->update(
                'project_applications',
                [
                    'status' => 'rejected',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => $auth->getUserId(),
                    'rejection_reason' => $rejectionReason
                ],
                'application_id = ?',
                [$applicationId]
            );

            if (!$updateResult) {
                throw new Exception('Failed to update application status');
            }

            // Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_rejected',
                "Rejected application #{$applicationId}: {$application['project_name']}",
                null,
                ['reason' => $rejectionReason]
            );

            // Send rejection email
            $emailContent = "<p>Dear {$application['project_lead_name']},</p>";
            $emailContent .= "<p>Thank you for your interest in the JHUB AFRICA program and for submitting your project application for <strong>{$application['project_name']}</strong>.</p>";
            $emailContent .= "<p>After careful review, we regret to inform you that your application has not been approved at this time.</p>";
            $emailContent .= "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
            $emailContent .= "<p><strong>Feedback:</strong></p>";
            $emailContent .= "<p>" . nl2br(htmlspecialchars($rejectionReason)) . "</p>";
            $emailContent .= "</div>";
            $emailContent .= "<p>We encourage you to:</p>";
            $emailContent .= "<ul>";
            $emailContent .= "<li>Review and refine your project based on this feedback</li>";
            $emailContent .= "<li>Strengthen your business model and market validation</li>";
            $emailContent .= "<li>Consider reapplying in the future with an improved proposal</li>";
            $emailContent .= "</ul>";
            $emailContent .= "<p>If you have questions about this decision, please contact us at applications@jhubafrica.com</p>";
            $emailContent .= "<p>Best regards,<br><strong>The JHUB AFRICA Team</strong></p>";

            sendEmailNotification(
                $application['project_lead_email'],
                'Update on Your JHUB AFRICA Application',
                $emailContent,
                NOTIFY_APPLICATION_REJECTED,
                array_merge($application, ['rejection_reason' => $rejectionReason])
            );

            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application rejected. Notification email has been sent to the applicant.'
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