<?php
// api/projects/terminate.php - Terminate Project (Admin Only)
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin authentication only
if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only admins can terminate projects.']);
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
    if (empty($input['project_id'])) {
        throw new Exception('Project ID is required');
    }

    if (empty($input['termination_reason'])) {
        throw new Exception('Termination reason is required');
    }

    $projectId = intval($input['project_id']);
    $terminationReason = trim($input['termination_reason']);
    $adminId = $auth->getUserId();

    // Get project info
    $project = $database->getRow(
        "SELECT * FROM projects WHERE project_id = ?",
        [$projectId]
    );

    if (!$project) {
        throw new Exception('Project not found');
    }

    // Check if already terminated or completed
    if ($project['status'] === 'terminated') {
        throw new Exception('Project is already terminated');
    }

    if ($project['status'] === 'completed') {
        throw new Exception('Cannot terminate a completed project');
    }

    // Start transaction
    $database->beginTransaction();

    // Update project status
    $updateResult = $database->update(
        'projects',
        [
            'status' => 'terminated',
            'termination_reason' => $terminationReason,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'project_id = ?',
        [$projectId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to terminate project');
    }

    // Deactivate all team members
    $database->update(
        'project_innovators',
        ['is_active' => 0],
        'project_id = ?',
        [$projectId]
    );

    // Deactivate all mentor assignments
    $database->update(
        'project_mentors',
        ['is_active' => 0],
        'project_id = ?',
        [$projectId]
    );

    // Log activity
    logActivity(
        'admin',
        $adminId,
        'project_terminated',
        "Terminated project: {$project['project_name']}",
        $projectId,
        ['termination_reason' => $terminationReason]
    );

    // Get all team members and mentors for notification
    $teamMembers = $database->getRows(
        "SELECT name, email FROM project_innovators WHERE project_id = ? AND email IS NOT NULL",
        [$projectId]
    );

    $mentors = $database->getRows(
        "SELECT m.name, m.email 
         FROM project_mentors pm
         INNER JOIN mentors m ON pm.mentor_id = m.mentor_id
         WHERE pm.project_id = ?",
        [$projectId]
    );

    // Commit transaction
    $database->commit();

    // Send notification emails
    $notificationMessage = "Dear Team Member,\n\n";
    $notificationMessage .= "We regret to inform you that the project '{$project['project_name']}' has been terminated.\n\n";
    $notificationMessage .= "Reason for termination:\n";
    $notificationMessage .= $terminationReason . "\n\n";
    $notificationMessage .= "All project activities have been suspended. If you have questions or concerns, please contact JHUB AFRICA support.\n\n";
    $notificationMessage .= "We appreciate your participation and hope to see you in future projects.\n\n";
    $notificationMessage .= "Best regards,\n";
    $notificationMessage .= "JHUB AFRICA Team";

    // Send to project lead
    sendEmailNotification(
        $project['project_lead_email'],
        'Project Terminated - JHUB AFRICA',
        $notificationMessage,
        'project_terminated'
    );

    // Send to all team members
    foreach ($teamMembers as $member) {
        if ($member['email'] !== $project['project_lead_email']) {
            sendEmailNotification(
                $member['email'],
                'Project Terminated - JHUB AFRICA',
                $notificationMessage,
                'project_terminated'
            );
        }
    }

    // Send to all mentors
    foreach ($mentors as $mentor) {
        sendEmailNotification(
            $mentor['email'],
            'Project Terminated - JHUB AFRICA',
            str_replace('Dear Team Member', "Dear {$mentor['name']}", $notificationMessage),
            'project_terminated'
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Project terminated successfully. Notifications have been sent to all team members and mentors.'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($database && method_exists($database, 'rollback')) {
        $database->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>