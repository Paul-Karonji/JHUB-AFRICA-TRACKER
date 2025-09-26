<?php
// api/applications/review.php
// Admin Application Review API

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

    if ($application['status'] !== 'pending') {
        throw new Exception('This application has already been reviewed');
    }

    $database->beginTransaction();

    try {
        if ($action === 'approve') {
            // Approve application and create project
            
            // Create project from application
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
                'current_stage' => 1,
                'status' => 'active',
                'created_from_application' => $applicationId,
                'created_by_admin' => $auth->getUserId()
            ];

            $projectId = $database->insert('projects', $projectData);

            if (!$projectId) {
                throw new Exception('Failed to create project');
            }

            // Update application status
            $database->update(
                'project_applications',
                [
                    'status' => 'approved',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => $auth->getUserId()
                ],
                'application_id = ?',
                [$applicationId]
            );

            // Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_approved',
                "Application #{$applicationId} approved and project #{$projectId} created",
                $projectId
            );

            // Send approval email
            $emailSubject = 'Congratulations! Your JHUB AFRICA Application Has Been Approved';
            $emailBody = "Dear {$application['project_lead_name']},\n\n";
            $emailBody .= "Congratulations! Your project application for '{$application['project_name']}' has been approved!\n\n";
            $emailBody .= "You can now access your project dashboard using these credentials:\n";
            $emailBody .= "Profile Name: {$application['profile_name']}\n";
            $emailBody .= "Login URL: " . SITE_URL . "/auth/project-login.php\n\n";
            $emailBody .= "Your project is currently at Stage 1: Project Creation. Our mentors will be reviewing available projects and may join to provide guidance.\n\n";
            $emailBody .= "Welcome to the JHUB AFRICA innovation journey!\n\n";
            $emailBody .= "Best regards,\nJHUB AFRICA Team";

            sendEmailNotification(
                $application['project_lead_email'],
                $emailSubject,
                $emailBody,
                NOTIFY_APPLICATION_APPROVED
            );

            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application approved successfully! Project has been created.',
                'project_id' => $projectId
            ]);

        } elseif ($action === 'reject') {
            // Reject application
            
            if (empty($rejectionReason)) {
                throw new Exception('Rejection reason is required');
            }

            // Update application status
            $database->update(
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

            // Log activity
            logActivity(
                USER_TYPE_ADMIN,
                $auth->getUserId(),
                'application_rejected',
                "Application #{$applicationId} rejected",
                null,
                ['reason' => $rejectionReason]
            );

            // Send rejection email
            $emailSubject = 'Update on Your JHUB AFRICA Application';
            $emailBody = "Dear {$application['project_lead_name']},\n\n";
            $emailBody .= "Thank you for your interest in the JHUB AFRICA program and for submitting your project application for '{$application['project_name']}'.\n\n";
            $emailBody .= "After careful review, we regret to inform you that your application has not been approved at this time.\n\n";
            $emailBody .= "Reason: {$rejectionReason}\n\n";
            $emailBody .= "We encourage you to refine your project based on this feedback and consider reapplying in the future. Our program is continuously evolving, and we look forward to seeing your innovation progress.\n\n";
            $emailBody .= "If you have any questions, please don't hesitate to contact us.\n\n";
            $emailBody .= "Best regards,\nJHUB AFRICA Team";

            sendEmailNotification(
                $application['project_lead_email'],
                $emailSubject,
                $emailBody,
                NOTIFY_APPLICATION_REJECTED
            );

            $database->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application rejected. Notification email has been sent.'
            ]);

        } else {
            throw new Exception('Invalid action');
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