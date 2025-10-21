<?php
/**
 * api/projects/mentors.php
 * 100% COMPLETE FILE - Mentor Assignment API with Approval Record Creation
 */

header('Content-Type: application/json');
require_once '../../includes/init.php';

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

    $userType = $auth->getUserType();
    $userId = $auth->getUserId();
    
    if (!isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    if (!isset($input['project_id'])) {
        throw new Exception('Project ID is required');
    }

    $action = $input['action'];
    $projectId = intval($input['project_id']);

    $project = $database->getRow(
        "SELECT * FROM projects WHERE project_id = ? AND status = 'active'",
        [$projectId]
    );

    if (!$project) {
        throw new Exception('Project not found or not active');
    }

    switch ($action) {
        case 'join':
            if ($userType !== USER_TYPE_MENTOR) {
                throw new Exception('Only mentors can join projects');
            }

            $mentorId = $userId;

            $existing = $database->getRow(
                "SELECT * FROM project_mentors WHERE project_id = ? AND mentor_id = ?",
                [$projectId, $mentorId]
            );

            if ($existing) {
                if ($existing['is_active']) {
                    throw new Exception('You are already assigned to this project');
                } else {
                    $updated = $database->update(
                        'project_mentors',
                        ['is_active' => 1, 'assigned_at' => date('Y-m-d H:i:s')],
                        'pm_id = ?',
                        [$existing['pm_id']]
                    );
                    
                    if (!$updated) {
                        throw new Exception('Failed to rejoin project');
                    }
                    
                    $assignmentId = $existing['pm_id'];
                }
            } else {
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

            if ($project['current_stage'] == 1) {
                $database->update(
                    'projects',
                    ['current_stage' => 2],
                    'project_id = ?',
                    [$projectId]
                );

                logActivity(
                    'system',
                    null,
                    'stage_updated',
                    "Project progressed to Stage 2 (Mentorship) after mentor assignment",
                    $projectId,
                    ['old_stage' => 1, 'new_stage' => 2]
                );
                
                resetMentorApprovalsForStage($projectId, 2);
            }
            
            createInitialMentorApproval($projectId, $mentorId);

            $mentor = $database->getRow(
                "SELECT * FROM mentors WHERE mentor_id = ?",
                [$mentorId]
            );

            logActivity(
                USER_TYPE_MENTOR,
                $userId,
                'mentor_joined',
                "Mentor {$mentor['name']} joined project",
                $projectId
            );

            sendEmailNotification(
                $project['project_lead_email'],
                'New Mentor Assigned to Your Project',
                "Good news! {$mentor['name']}, an expert in {$mentor['area_of_expertise']}, has joined your project '{$project['project_name']}' as a mentor.\n\nBest regards,\nJHUB AFRICA Team",
                'mentor_assigned',
                $projectId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Successfully joined project!',
                'assignment_id' => $assignmentId
            ]);
            break;

        case 'leave':
            if ($userType !== USER_TYPE_MENTOR && $userType !== USER_TYPE_ADMIN) {
                throw new Exception('Access denied');
            }

            $mentorId = ($userType === USER_TYPE_ADMIN && isset($input['mentor_id'])) 
                ? intval($input['mentor_id']) 
                : $userId;

            $assignment = $database->getRow(
                "SELECT * FROM project_mentors 
                 WHERE mentor_id = ? AND project_id = ? AND is_active = 1",
                [$mentorId, $projectId]
            );

            if (!$assignment) {
                throw new Exception('Mentor is not assigned to this project');
            }

            $database->update(
                'project_mentors',
                ['is_active' => 0],
                'pm_id = ?',
                [$assignment['pm_id']]
            );

            $mentor = $database->getRow(
                "SELECT * FROM mentors WHERE mentor_id = ?",
                [$mentorId]
            );

            logActivity(
                $userType,
                $userId,
                'mentor_left',
                "Mentor {$mentor['name']} left project",
                $projectId
            );

            $database->update(
                'mentor_stage_approvals',
                ['approved_for_next_stage' => 0],
                'project_id = ? AND mentor_id = ?',
                [$projectId, $mentorId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Mentor has left the project'
            ]);
            break;

        case 'assign':
            if ($userType !== USER_TYPE_ADMIN) {
                throw new Exception('Only administrators can assign mentors');
            }

            if (!isset($input['mentor_id'])) {
                throw new Exception('Mentor ID is required');
            }

            $mentorId = intval($input['mentor_id']);

            $mentor = $database->getRow(
                "SELECT * FROM mentors WHERE mentor_id = ? AND is_active = 1",
                [$mentorId]
            );

            if (!$mentor) {
                throw new Exception('Mentor not found or inactive');
            }

            $existing = $database->getRow(
                "SELECT * FROM project_mentors WHERE project_id = ? AND mentor_id = ?",
                [$projectId, $mentorId]
            );

            if ($existing) {
                if ($existing['is_active']) {
                    throw new Exception('Mentor is already assigned to this project');
                } else {
                    $database->update(
                        'project_mentors',
                        ['is_active' => 1, 'assigned_at' => date('Y-m-d H:i:s')],
                        'pm_id = ?',
                        [$existing['pm_id']]
                    );
                    $assignmentId = $existing['pm_id'];
                }
            } else {
                $assignmentData = [
                    'project_id' => $projectId,
                    'mentor_id' => $mentorId,
                    'assigned_by_mentor' => 0,
                    'is_active' => 1
                ];
                
                $assignmentId = $database->insert('project_mentors', $assignmentData);
                
                if (!$assignmentId) {
                    throw new Exception('Failed to assign mentor');
                }
            }

            if ($project['current_stage'] == 1) {
                $database->update(
                    'projects',
                    ['current_stage' => 2],
                    'project_id = ?',
                    [$projectId]
                );

                logActivity(
                    'system',
                    null,
                    'stage_updated',
                    "Project progressed to Stage 2 (Mentorship) after mentor assignment",
                    $projectId,
                    ['old_stage' => 1, 'new_stage' => 2]
                );
                
                resetMentorApprovalsForStage($projectId, 2);
            }

            createInitialMentorApproval($projectId, $mentorId);

            logActivity(
                USER_TYPE_ADMIN,
                $userId,
                'mentor_assigned',
                "Admin assigned mentor {$mentor['name']} to project",
                $projectId
            );

            sendEmailNotification(
                $mentor['email'],
                'You Have Been Assigned to a Project',
                "Hi {$mentor['name']},\n\nYou have been assigned to mentor '{$project['project_name']}'.\n\nBest regards,\nJHUB AFRICA Team",
                'mentor_assigned',
                $projectId
            );

            sendEmailNotification(
                $project['project_lead_email'],
                'New Mentor Assigned to Your Project',
                "Good news! {$mentor['name']} has been assigned to mentor '{$project['project_name']}'.\n\nBest regards,\nJHUB AFRICA Team",
                'mentor_assigned',
                $projectId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Mentor assigned successfully',
                'assignment_id' => $assignmentId
            ]);
            break;

        case 'remove':
            if ($userType !== USER_TYPE_ADMIN) {
                throw new Exception('Only administrators can remove mentors');
            }

            if (!isset($input['mentor_id'])) {
                throw new Exception('Mentor ID is required');
            }

            $mentorId = intval($input['mentor_id']);

            $assignment = $database->getRow(
                "SELECT * FROM project_mentors 
                 WHERE mentor_id = ? AND project_id = ? AND is_active = 1",
                [$mentorId, $projectId]
            );

            if (!$assignment) {
                throw new Exception('Mentor is not assigned to this project');
            }

            $database->update(
                'project_mentors',
                ['is_active' => 0],
                'pm_id = ?',
                [$assignment['pm_id']]
            );

            $mentor = $database->getRow(
                "SELECT * FROM mentors WHERE mentor_id = ?",
                [$mentorId]
            );

            logActivity(
                USER_TYPE_ADMIN,
                $userId,
                'mentor_removed',
                "Admin removed mentor {$mentor['name']} from project",
                $projectId
            );

            $database->update(
                'mentor_stage_approvals',
                ['approved_for_next_stage' => 0],
                'project_id = ? AND mentor_id = ?',
                [$projectId, $mentorId]
            );

            sendEmailNotification(
                $mentor['email'],
                'Removed from Project',
                "Hi {$mentor['name']},\n\nYou have been removed from '{$project['project_name']}'.\n\nBest regards,\nJHUB AFRICA Team",
                'system_alert',
                $projectId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Mentor removed successfully'
            ]);
            break;

        case 'list':
            $mentors = $database->getRows(
                "SELECT m.*, 
                        pm.assigned_at, 
                        pm.is_active,
                        msa.approved_for_next_stage,
                        msa.approval_date
                 FROM mentors m
                 INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
                 LEFT JOIN mentor_stage_approvals msa ON m.mentor_id = msa.mentor_id 
                           AND msa.project_id = pm.project_id 
                           AND msa.current_stage = ?
                 WHERE pm.project_id = ?
                 ORDER BY pm.assigned_at DESC",
                [$project['current_stage'], $projectId]
            );

            echo json_encode([
                'success' => true,
                'mentors' => $mentors,
                'project' => [
                    'id' => $project['project_id'],
                    'name' => $project['project_name'],
                    'current_stage' => $project['current_stage'],
                    'status' => $project['status']
                ]
            ]);
            break;

        case 'get_approval_status':
            $consensus = getProjectConsensusStatus($projectId);
            $mentorsWithStatus = getMentorsWithApprovalStatus($projectId, $project['current_stage']);

            echo json_encode([
                'success' => true,
                'consensus' => $consensus,
                'mentors' => $mentorsWithStatus,
                'project' => [
                    'id' => $project['project_id'],
                    'name' => $project['project_name'],
                    'current_stage' => $project['current_stage']
                ]
            ]);
            break;

        default:
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}