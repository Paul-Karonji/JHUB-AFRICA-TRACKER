<?php
// api/mentors/leave-project.php
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

    // Get assignment
    $assignment = $database->getRow(
        "SELECT pm.*, p.project_name
         FROM project_mentors pm
         INNER JOIN projects p ON pm.project_id = p.project_id
         WHERE pm.project_id = ? AND pm.mentor_id = ? AND pm.is_active = 1",
        [$projectId, $mentorId]
    );

    if (!$assignment) {
        throw new Exception('You are not assigned to this project');
    }

    // Deactivate assignment
    $updated = $database->update(
        'project_mentors',
        ['is_active' => 0],
        'pm_id = ?',
        [$assignment['pm_id']]
    );

    if (!$updated) {
        throw new Exception('Failed to leave project');
    }

    // Log activity
    logActivity(
        'mentor',
        $mentorId,
        'project_left',
        "Left project: {$assignment['project_name']}",
        null,
        ['project_id' => $projectId]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Successfully left the project'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Mentor leave project error: ' . $e->getMessage());
}