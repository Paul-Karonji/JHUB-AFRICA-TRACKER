<?php
// api/projects/update-stage.php
// Updated stage progression with mentor consensus requirement
header('Content-Type: application/json');
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

// Require authentication
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
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
    if (!isset($input['project_id']) || !isset($input['action'])) {
        throw new Exception('Project ID and action are required');
    }

    $projectId = intval($input['project_id']);
    $action = $input['action'];
    $userId = $auth->getUserId();
    $userType = $auth->getUserType();

    // Get project details
    $project = $database->getRow("
        SELECT project_id, current_stage, project_name, status 
        FROM projects 
        WHERE project_id = ?
    ", [$projectId]);

    if (!$project) {
        throw new Exception('Project not found');
    }

    if ($project['status'] !== 'active') {
        throw new Exception('Cannot modify inactive project');
    }

    switch ($action) {
        case 'approve_progression':
            // Mentor approves progression to next stage
            if ($userType !== USER_TYPE_MENTOR) {
                throw new Exception('Only mentors can approve stage progression');
            }

            // Verify mentor is assigned to project
            $assignment = $database->getRow("
                SELECT * FROM project_mentors 
                WHERE project_id = ? AND mentor_id = ? AND is_active = 1
            ", [$projectId, $userId]);

            if (!$assignment) {
                throw new Exception('You are not assigned to this project');
            }

            $result = progressProjectStageWithConsensus($projectId, $userId, false);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'new_stage' => $result['new_stage'] ?? null
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'consensus_status' => $result['consensus_status'] ?? null
                ]);
            }
            break;

        case 'admin_override_stage':
            // Admin override - force stage progression or set specific stage
            if ($userType !== USER_TYPE_ADMIN) {
                throw new Exception('Only administrators can override stage progression');
            }

            if (!isset($input['target_stage'])) {
                throw new Exception('Target stage is required for admin override');
            }

            $targetStage = intval($input['target_stage']);
            
            if ($targetStage < 1 || $targetStage > 6) {
                throw new Exception('Invalid target stage');
            }

            // Direct stage update with admin override
            $updateData = ['current_stage' => $targetStage];
            
            if ($targetStage == 6) {
                $updateData['status'] = 'completed';
                $updateData['completion_date'] = date('Y-m-d H:i:s');
            }

            $updated = $database->update(
                'projects',
                $updateData,
                'project_id = ?',
                [$projectId]
            );

            if (!$updated) {
                throw new Exception('Failed to update project stage');
            }

            // Log the admin override
            logActivity(
                'admin',
                $userId,
                'stage_override',
                "Admin override: Set project stage to {$targetStage}",
                $projectId,
                ['old_stage' => $project['current_stage'], 'new_stage' => $targetStage]
            );

            // Send notification
            $projectData = $database->getRow("SELECT project_name, project_lead_email FROM projects WHERE project_id = ?", [$projectId]);
            sendEmailNotification(
                $projectData['project_lead_email'],
                'Project Stage Updated by Administrator',
                "Your project '{$projectData['project_name']}' has been updated to Stage {$targetStage} by an administrator.\n\nBest regards,\nJHUB AFRICA Team",
                NOTIFY_STAGE_UPDATED
            );

            echo json_encode([
                'success' => true,
                'message' => "Project stage updated to {$targetStage} (Admin Override)",
                'new_stage' => $targetStage
            ]);
            break;

        case 'get_consensus_status':
            // Get current consensus status for mentors
            $currentStage = $project['current_stage'];
            $consensus = checkMentorConsensusForStageProgression($projectId, $currentStage);
            $approvals = getMentorApprovalStatus($projectId, $currentStage);

            echo json_encode([
                'success' => true,
                'project_stage' => $currentStage,
                'consensus' => $consensus,
                'mentor_approvals' => $approvals
            ]);
            break;

        case 'revoke_approval':
            // Mentor revokes their approval
            if ($userType !== USER_TYPE_MENTOR) {
                throw new Exception('Only mentors can revoke their approval');
            }

            // Verify mentor is assigned to project
            $assignment = $database->getRow("
                SELECT * FROM project_mentors 
                WHERE project_id = ? AND mentor_id = ? AND is_active = 1
            ", [$projectId, $userId]);

            if (!$assignment) {
                throw new Exception('You are not assigned to this project');
            }

            $revoked = recordMentorStageApproval($projectId, $userId, $project['current_stage'], false);

            if ($revoked) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Your approval has been revoked'
                ]);
            } else {
                throw new Exception('Failed to revoke approval');
            }
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Stage update error: ' . $e->getMessage());
}
?>