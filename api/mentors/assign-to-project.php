<?php
// api/mentors/assign-to-project.php
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require mentor authentication
if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_MENTOR) {
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
    if (!isset($input['project_id'])) {
        throw new Exception('Project ID is required');
    }

    $projectId = intval($input['project_id']);
    $mentorId = $auth->getUserId();

    // Get project info
    $project = $database->getRow(
        "SELECT * FROM projects WHERE project_id = ? AND status = 'active'",
        [$projectId]
    );

    if (!$project) {
        throw new Exception('Project not found or not active');
    }

    // Check if mentor is already assigned
    $existing = $database->getRow(
        "SELECT * FROM project_mentors WHERE project_id = ? AND mentor_id = ?",
        [$projectId, $mentorId]
    );

    if ($existing) {
        if ($existing['is_active']) {
            throw new Exception('You are already assigned to this project');
        } else {
            // Reactivate assignment
            $updated = $database->update(
                'project_mentors',
                ['is_active' => 1, 'assigned_at' => date('Y-m-d H:i:s')],
                'pm_id = ?',
                [$existing['pm_id']]
            );
            
            if (!$updated) {
                throw new Exception('Failed to rejoin project');
            }
        }
    } else {
        // Create new assignment
        $assignmentData = [
            'project_id' => $projectId,
            'mentor_id' => $mentorId,
            'assigned_by_mentor' => 1,
            'is_active' => 1
        ];
        
        $assignmentId = $database->insert('project_mentors', $assignmentData);
        
        if (!$assignmentId) {
            throw new Exception('Failed to join project');
        }
    }

    // Update project stage if still in stage 1
    if ($project['current_stage'] == 1) {
        $database->update('projects', ['current_stage' => 2], 'project_id = ?', [$projectId]);
    }

    // Get mentor info
    $mentor = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);

    // Log activity
    logActivity(
        'mentor',
        $mentorId,
        'project_joined',
        "Joined project: {$project['project_name']}",
        null,
        ['project_id' => $projectId]
    );

    // Send email notification to project lead
    sendEmailNotification(
        $project['project_lead_email'],
        'New Mentor Assigned - JHUB AFRICA',
        "Dear {$project['project_lead_name']},\n\nGood news! {$mentor['name']} has joined your project '{$project['project_name']}' as a mentor.\n\nMentor Expertise: {$mentor['area_of_expertise']}\n\nYou can now collaborate with your mentor through the platform.\n\nBest regards,\nJHUB AFRICA Team",
        'mentor_assigned'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Successfully joined project as mentor!',
        'project_id' => $projectId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Mentor assign error: ' . $e->getMessage());
}

