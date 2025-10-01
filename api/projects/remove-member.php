<?php
// api/projects/remove-member.php - Remove Team Member from Project
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin or mentor authentication
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userType = $auth->getUserType();
if (!in_array($userType, [USER_TYPE_ADMIN, USER_TYPE_MENTOR])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to remove team members']);
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
    if (empty($input['member_id'])) {
        throw new Exception('Member ID is required');
    }

    $memberId = intval($input['member_id']);
    $userId = $auth->getUserId();

    // Get team member info
    $member = $database->getRow(
        "SELECT pi.*, p.project_name, p.project_lead_email 
         FROM project_innovators pi
         INNER JOIN projects p ON pi.project_id = p.project_id
         WHERE pi.pi_id = ?",
        [$memberId]
    );

    if (!$member) {
        throw new Exception('Team member not found');
    }

    // If mentor, verify they are assigned to this project
    if ($userType === USER_TYPE_MENTOR) {
        $isMentorAssigned = isMentorAssignedToProject($userId, $member['project_id']);
        if (!$isMentorAssigned) {
            throw new Exception('You are not assigned to this project');
        }
    }

    // Prevent removing project lead
    if (strtolower($member['role']) === 'project lead' || $member['email'] === $member['project_lead_email']) {
        throw new Exception('Cannot remove the project lead. Contact an administrator if needed.');
    }

    // Soft delete (set is_active to 0)
    $updateResult = $database->update(
        'project_innovators',
        ['is_active' => 0],
        'pi_id = ?',
        [$memberId]
    );

    if (!$updateResult) {
        throw new Exception('Failed to remove team member');
    }

    // Log activity
    logActivity(
        $userType,
        $userId,
        'team_member_removed',
        "Removed team member {$member['name']} from project {$member['project_name']}",
        $member['project_id'],
        ['member_id' => $memberId, 'member_name' => $member['name'], 'member_email' => $member['email']]
    );

    // Send notification email to removed member (optional)
    $notificationMessage = "Dear {$member['name']},\n\n";
    $notificationMessage .= "You have been removed from the project '{$member['project_name']}'.\n\n";
    $notificationMessage .= "If you believe this was done in error, please contact the project lead or JHUB AFRICA support.\n\n";
    $notificationMessage .= "Best regards,\n";
    $notificationMessage .= "JHUB AFRICA Team";

    sendEmailNotification(
        $member['email'],
        'Project Team Update - JHUB AFRICA',
        $notificationMessage,
        'team_member_removed'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Team member removed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>