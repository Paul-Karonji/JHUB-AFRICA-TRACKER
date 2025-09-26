<?php
// api/projects/mentors.php
// Mentor assignment to projects API

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require authentication
if (!$auth->isValidSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userType = $auth->getUserType();
$userId = $auth->getUserId();

try {
    // Handle POST requests (join/leave project)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid request data');
        }

        // Validate CSRF token
        if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
            throw new Exception('Invalid security token');
        }

        $action = $input['action'] ?? '';
        $projectId = intval($input['project_id'] ?? 0);

        if (!$projectId) {
            throw new Exception('Project ID is required');
        }

        // Verify project exists and is active
        $project = $database->getRow(
            "SELECT * FROM projects WHERE project_id = ? AND status = 'active'",
            [$projectId]
        );

        if (!$project) {
            throw new Exception('Project not found or not active');
        }

        switch ($action) {
            case 'join':
                // Mentor joins project (self-assignment)
                if ($userType !== USER_TYPE_MENTOR) {
                    throw new Exception('Only mentors can join projects');
                }

                // Check if already assigned
                $existing = $database->getRow(
                    "SELECT * FROM project_mentors 
                     WHERE mentor_id = ? AND project_id = ? AND is_active = 1",
                    [$userId, $projectId]
                );

                if ($existing) {
                    throw new Exception('You are already assigned to this project');
                }

                // Add mentor assignment
                $assignmentData = [
                    'project_id' => $projectId,
                    'mentor_id' => $userId,
                    'assigned_by_mentor' => 1,
                    'is_active' => 1
                ];

                $assignmentId = $database->insert('project_mentors', $assignmentData);

                if (!$assignmentId) {
                    throw new Exception('Failed to join project');
                }

                // Get mentor details
                $mentor = $database->getRow(
                    "SELECT * FROM mentors WHERE mentor_id = ?",
                    [$userId]
                );

                // Progress to Stage 2 if still in Stage 1
                if ($project['current_stage'] == 1) {
                    $database->update(
                        'projects',
                        ['current_stage' => 2],
                        'project_id = ?',
                        [$projectId]
                    );

                    // Log stage progression
                    logActivity(
                        'system',
                        null,
                        'stage_updated',
                        "Project progressed to Stage 2 (Mentorship) after mentor assignment",
                        $projectId,
                        ['old_stage' => 1, 'new_stage' => 2]
                    );
                }

                // Log activity
                logActivity(
                    USER_TYPE_MENTOR,
                    $userId,
                    'mentor_joined',
                    "Mentor {$mentor['name']} joined project",
                    $projectId
                );

                // Send notification to project lead
                sendEmailNotification(
                    $project['project_lead_email'],
                    'New Mentor Assigned to Your Project',
                    "Good news! {$mentor['name']}, an expert in {$mentor['area_of_expertise']}, has joined your project '{$project['project_name']}' as a mentor.\n\nYou can now collaborate with your mentor through the project dashboard.\n\nBest regards,\nJHUB AFRICA Team",
                    NOTIFY_MENTOR_ASSIGNED
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Successfully joined project! You can now mentor this innovation.',
                    'assignment_id' => $assignmentId
                ]);
                break;

            case 'leave':
                // Mentor leaves project
                if ($userType !== USER_TYPE_MENTOR && $userType !== USER_TYPE_ADMIN) {
                    throw new Exception('Access denied');
                }

                $mentorId = ($userType === USER_TYPE_ADMIN && isset($input['mentor_id'])) 
                    ? intval($input['mentor_id']) 
                    : $userId;

                // Check if assigned
                $assignment = $database->getRow(
                    "SELECT * FROM project_mentors 
                     WHERE mentor_id = ? AND project_id = ? AND is_active = 1",
                    [$mentorId, $projectId]
                );

                if (!$assignment) {
                    throw new Exception('Mentor is not assigned to this project');
                }

                // Soft delete assignment
                $database->update(
                    'project_mentors',
                    ['is_active' => 0],
                    'pm_id = ?',
                    [$assignment['pm_id']]
                );

                // Get mentor details
                $mentor = $database->getRow(
                    "SELECT * FROM mentors WHERE mentor_id = ?",
                    [$mentorId]
                );

                // Log activity
                logActivity(
                    $userType,
                    $userId,
                    'mentor_left',
                    "Mentor {$mentor['name']} left project",
                    $projectId
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Successfully left project. Your contributions remain with the project.'
                ]);
                break;

            case 'assign':
                // Admin assigns mentor to project
                if ($userType !== USER_TYPE_ADMIN) {
                    throw new Exception('Only admins can assign mentors');
                }

                $mentorId = intval($input['mentor_id'] ?? 0);

                if (!$mentorId) {
                    throw new Exception('Mentor ID is required');
                }

                // Verify mentor exists
                $mentor = $database->getRow(
                    "SELECT * FROM mentors WHERE mentor_id = ? AND is_active = 1",
                    [$mentorId]
                );

                if (!$mentor) {
                    throw new Exception('Mentor not found');
                }

                // Check if already assigned
                $existing = $database->getRow(
                    "SELECT * FROM project_mentors 
                     WHERE mentor_id = ? AND project_id = ? AND is_active = 1",
                    [$mentorId, $projectId]
                );

                if ($existing) {
                    throw new Exception('Mentor is already assigned to this project');
                }

                // Add assignment
                $assignmentData = [
                    'project_id' => $projectId,
                    'mentor_id' => $mentorId,
                    'assigned_by_mentor' => 0,
                    'is_active' => 1,
                    'notes' => $input['notes'] ?? null
                ];

                $assignmentId = $database->insert('project_mentors', $assignmentData);

                if (!$assignmentId) {
                    throw new Exception('Failed to assign mentor');
                }

                // Progress to Stage 2 if still in Stage 1
                if ($project['current_stage'] == 1) {
                    $database->update(
                        'projects',
                        ['current_stage' => 2],
                        'project_id = ?',
                        [$projectId]
                    );
                }

                // Log activity
                logActivity(
                    USER_TYPE_ADMIN,
                    $userId,
                    'mentor_assigned',
                    "Admin assigned mentor {$mentor['name']} to project",
                    $projectId
                );

                // Send notifications
                sendEmailNotification(
                    $mentor['email'],
                    'You Have Been Assigned to a Project',
                    "You have been assigned as a mentor to the project '{$project['project_name']}'.\n\nPlease login to your mentor dashboard to view project details and start mentoring.\n\nBest regards,\nJHUB AFRICA Team",
                    NOTIFY_MENTOR_ASSIGNED
                );

                sendEmailNotification(
                    $project['project_lead_email'],
                    'New Mentor Assigned to Your Project',
                    "Good news! {$mentor['name']}, an expert in {$mentor['area_of_expertise']}, has been assigned to your project '{$project['project_name']}'.\n\nBest regards,\nJHUB AFRICA Team",
                    NOTIFY_MENTOR_ASSIGNED
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Mentor assigned successfully',
                    'assignment_id' => $assignmentId
                ]);
                break;

            default:
                throw new Exception('Invalid action');
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get mentors for a project
        $projectId = intval($_GET['project_id'] ?? 0);

        if (!$projectId) {
            throw new Exception('Project ID is required');
        }

        $mentors = $database->getRows("
            SELECT m.*, pm.assigned_at, pm.notes, pm.assigned_by_mentor
            FROM project_mentors pm
            INNER JOIN mentors m ON pm.mentor_id = m.mentor_id
            WHERE pm.project_id = ? AND pm.is_active = 1
            ORDER BY pm.assigned_at ASC
        ", [$projectId]);

        echo json_encode([
            'success' => true,
            'data' => $mentors,
            'count' => count($mentors)
        ]);

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Mentor assignment error: ' . $e->getMessage());
}