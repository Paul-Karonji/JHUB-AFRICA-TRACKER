<?php
// api/projects/details.php
// Get detailed project information

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require authentication
if (!$auth->isValidSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    // Get project ID
    if (!isset($_GET['id'])) {
        throw new Exception('Project ID is required');
    }

    $projectId = intval($_GET['id']);
    $userType = $auth->getUserType();
    $userId = $auth->getUserId();

    // Check permissions
    if ($userType === USER_TYPE_PROJECT && $userId != $projectId) {
        throw new Exception('Access denied');
    } elseif ($userType === USER_TYPE_MENTOR) {
        // Check if mentor is assigned to this project
        $isAssigned = isMentorAssignedToProject($userId, $projectId);
        if (!$isAssigned) {
            throw new Exception('Access denied - You are not assigned to this project');
        }
    }

    // Get project details
    $project = $database->getRow("
        SELECT p.*,
               COUNT(DISTINCT pm.mentor_id) as mentor_count,
               COUNT(DISTINCT pi.pi_id) as team_count
        FROM projects p
        LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
        LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
        WHERE p.project_id = ?
        GROUP BY p.project_id
    ", [$projectId]);

    if (!$project) {
        throw new Exception('Project not found');
    }

    // Get team members
    $project['team_members'] = $database->getRows("
        SELECT * FROM project_innovators
        WHERE project_id = ? AND is_active = 1
        ORDER BY added_at ASC
    ", [$projectId]);

    // Get mentors
    $project['mentors'] = $database->getRows("
        SELECT m.*, pm.assigned_at, pm.notes
        FROM project_mentors pm
        INNER JOIN mentors m ON pm.mentor_id = m.mentor_id
        WHERE pm.project_id = ? AND pm.is_active = 1
        ORDER BY pm.assigned_at ASC
    ", [$projectId]);

    // Get recent comments count
    $project['comments_count'] = $database->count(
        'comments',
        'project_id = ? AND is_deleted = 0',
        [$projectId]
    );

    // Get resources count
    $project['resources_count'] = $database->count(
        'mentor_resources',
        'project_id = ?',
        [$projectId]
    );

    // Get assessments count
    $project['assessments_count'] = $database->count(
        'project_assessments',
        'project_id = ?',
        [$projectId]
    );

    // Format dates
    $project['created_at_formatted'] = formatDate($project['created_at']);
    $project['stage_name'] = getStageName($project['current_stage']);
    $project['stage_description'] = getStageDescription($project['current_stage']);
    $project['stage_progress'] = getStageProgress($project['current_stage']);

    echo json_encode([
        'success' => true,
        'data' => $project
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Project details error: ' . $e->getMessage());
}