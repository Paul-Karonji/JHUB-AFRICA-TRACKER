<?php
// api/comments/get-comment.php - API endpoint to get single comment details
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validate required parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Comment ID is required');
    }

    $commentId = intval($_GET['id']);

    // Require admin authentication for accessing comment details
    if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_ADMIN) {
        throw new Exception('Administrative access required');
    }

    // Get comment details
    $comment = $database->getRow("
        SELECT c.*, p.project_name
        FROM comments c
        INNER JOIN projects p ON c.project_id = p.project_id
        WHERE c.comment_id = ?
    ", [$commentId]);

    if (!$comment) {
        throw new Exception('Comment not found');
    }

    echo json_encode([
        'success' => true,
        'comment' => $comment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Get comment error: ' . $e->getMessage());
}

// =====================================================================================
// includes/functions.php - Updated with new consensus and moderation functions
// =====================================================================================

<?php
// includes/functions.php
// Updated core functions with mentor consensus and comment moderation support

// Include the mentor consensus functions
require_once __DIR__ . '/mentor-consensus-functions.php';

/**
 * Enhanced project metrics with consensus status
 */
function getProjectMetrics($projectId) {
    global $database;
    
    $metrics = [
        'team_size' => $database->count('project_innovators', 'project_id = ? AND is_active = 1', [$projectId]),
        'mentor_count' => $database->count('project_mentors', 'project_id = ? AND is_active = 1', [$projectId]),
        'resources_count' => $database->count('mentor_resources', 'project_id = ? AND is_deleted = 0', [$projectId]),
        'assessments_count' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0', [$projectId]),
        'approved_comments_count' => $database->count('comments', 'project_id = ? AND is_deleted = 0 AND (commenter_type != "investor" OR is_approved = 1)', [$projectId]),
        'pending_comments_count' => $database->count('comments', 'project_id = ? AND commenter_type = "investor" AND is_approved = 0 AND is_deleted = 0', [$projectId])
    ];
    
    // Add consensus information
    $project = $database->getRow("SELECT current_stage FROM projects WHERE project_id = ?", [$projectId]);
    if ($project) {
        $metrics['consensus_status'] = checkMentorConsensusForStageProgression($projectId, $project['current_stage']);
    }
    
    return $metrics;
}

/**
 * Enhanced project team lookup with better formatting
 */
function getProjectTeam($projectId) {
    global $database;
    
    return $database->getRows("
        SELECT pi.*, 
               CASE 
                   WHEN LOWER(pi.role) LIKE '%lead%' THEN 1
                   WHEN LOWER(pi.role) LIKE '%manager%' THEN 2
                   ELSE 3
               END as role_priority
        FROM project_innovators pi
        WHERE pi.project_id = ? AND pi.is_active = 1
        ORDER BY role_priority ASC, pi.added_at ASC
    ", [$projectId]);
}

/**
 * Enhanced mentor lookup with approval status
 */
function getProjectMentors($projectId) {
    global $database;
    
    $mentors = $database->getRows("
        SELECT m.*, pm.assigned_at, pm.notes,
               msa.approved_for_next_stage, msa.approval_date
        FROM mentors m
        INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
        LEFT JOIN mentor_stage_approvals msa ON m.mentor_id = msa.mentor_id 
            AND msa.project_id = pm.project_id 
            AND msa.current_stage = (SELECT current_stage FROM projects WHERE project_id = pm.project_id)
        WHERE pm.project_id = ? AND pm.is_active = 1
        ORDER BY pm.assigned_at ASC
    ", [$projectId]);
    
    return $mentors;
}

/**
 * Enhanced activity logging with better categorization
 */
function logActivity($userType, $userId, $action, $description, $projectId = null, $additionalData = null) {
    global $database;
    
    try {
        $activityData = [
            'user_type' => $userType,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'related_project_id' => $projectId,
            'additional_data' => $additionalData ? json_encode($additionalData) : null
        ];
        
        return $database->insert('activity_logs', $activityData);
        
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced email notification with better templating
 */
function sendEmailNotification($recipientEmail, $subject, $messageBody, $notificationType, $projectId = null) {
    global $database;
    
    try {
        // Queue the email notification
        $notificationData = [
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'message_body' => $messageBody,
            'notification_type' => $notificationType,
            'related_project_id' => $projectId,
            'status' => 'pending'
        ];
        
        $notificationId = $database->insert('email_notifications', $notificationData);
        
        // In a real implementation, you would process the email queue
        // For now, we'll just log it
        error_log("Email queued: {$subject} to {$recipientEmail}");
        
        return $notificationId;
        
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced stage progression with consensus checking
 */
function canProjectProgress($projectId) {
    $project = getProjectById($projectId);
    if (!$project) {
        return false;
    }
    
    // Check if project is at final stage
    if ($project['current_stage'] >= 6) {
        return false;
    }
    
    // Check mentor consensus
    $consensus = checkMentorConsensusForStageProgression($projectId, $project['current_stage']);
    return $consensus['can_progress'];
}

/**
 * Get project by ID with enhanced details
 */
function getProjectById($projectId) {
    global $database;
    
    $project = $database->getRow("
        SELECT p.*,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count
        FROM projects p
        WHERE p.project_id = ?
    ", [$projectId]);
    
    if ($project) {
        $project['stage_name'] = getStageName($project['current_stage']);
        $project['stage_description'] = getStageDescription($project['current_stage']);
        $project['stage_progress'] = getStageProgress($project['current_stage']);
        $project['consensus_status'] = checkMentorConsensusForStageProgression($projectId, $project['current_stage']);
    }
    
    return $project;
}

/**
 * Enhanced comment visibility check
 */
function getProjectComments($projectId, $viewerType = null, $viewerId = null, $includeReplies = true) {
    return getVisibleProjectComments($projectId, $viewerType, $viewerId);
}

/**
 * Check if user can moderate comments
 */
function canModerateComments($userType) {
    return $userType === USER_TYPE_ADMIN;
}

/**
 * Check if user can override stage progression
 */
function canOverrideStageProgression($userType) {
    return $userType === USER_TYPE_ADMIN;
}

/**
 * Get pending moderation counts for dashboard
 */
function getPendingModerationCounts() {
    global $database;
    
    return [
        'pending_comments' => $database->count('comments', 
            'commenter_type = "investor" AND is_approved = 0 AND is_deleted = 0'
        ),
        'projects_needing_consensus' => $database->count('projects p', 
            'p.status = "active" AND p.current_stage < 6 AND EXISTS (
                SELECT 1 FROM project_mentors pm 
                WHERE pm.project_id = p.project_id AND pm.is_active = 1
            )'
        )
    ];
}

/**
 * Enhanced stage progression statistics
 */
function getStageProgressionStats() {
    global $database;
    
    $stats = [];
    
    // Projects by stage
    for ($stage = 1; $stage <= 6; $stage++) {
        $stats['stage_' . $stage] = $database->count('projects', 
            'current_stage = ? AND status = "active"', [$stage]
        );
    }
    
    // Projects ready to progress
    $readyToProgress = 0;
    $projects = $database->getRows("
        SELECT project_id, current_stage 
        FROM projects 
        WHERE status = 'active' AND current_stage < 6
    ");
    
    foreach ($projects as $project) {
        $consensus = checkMentorConsensusForStageProgression($project['project_id'], $project['current_stage']);
        if ($consensus['can_progress']) {
            $readyToProgress++;
        }
    }
    
    $stats['ready_to_progress'] = $readyToProgress;
    $stats['total_active'] = $database->count('projects', 'status = "active"');
    $stats['completed'] = $database->count('projects', 'status = "completed"');
    
    return $stats;
}

/**
 * Enhanced user permissions check
 */
function hasPermission($action, $userType, $resourceId = null, $userId = null) {
    switch ($action) {
        case 'moderate_comments':
            return $userType === USER_TYPE_ADMIN;
            
        case 'override_stage':
            return $userType === USER_TYPE_ADMIN;
            
        case 'approve_stage_progression':
            if ($userType !== USER_TYPE_MENTOR || !$resourceId || !$userId) {
                return false;
            }
            // Check if mentor is assigned to project
            global $database;
            return $database->exists('project_mentors', 
                'project_id = ? AND mentor_id = ? AND is_active = 1', 
                [$resourceId, $userId]
            );
            
        case 'view_admin_comments':
            // Admins can only see their own comments, not other admin comments
            return false;
            
        case 'view_pending_comments':
            return $userType === USER_TYPE_ADMIN;
            
        default:
            return false;
    }
}

/**
 * Enhanced notification system
 */
function notifyMentorsOfStageProgression($projectId, $oldStage, $newStage) {
    global $database;
    
    $project = $database->getRow("SELECT project_name FROM projects WHERE project_id = ?", [$projectId]);
    $mentors = $database->getRows("
        SELECT m.name, m.email
        FROM mentors m
        INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
        WHERE pm.project_id = ? AND pm.is_active = 1
    ", [$projectId]);
    
    foreach ($mentors as $mentor) {
        sendEmailNotification(
            $mentor['email'],
            "Project Advanced: {$project['project_name']}",
            "The project '{$project['project_name']}' has progressed from Stage {$oldStage} to Stage {$newStage}.\n\nBest regards,\nJHUB AFRICA Team",
            NOTIFY_STAGE_UPDATED,
            $projectId
        );
    }
}

/**
 * Helper function to validate project access
 */
function validateProjectAccess($projectId, $userType, $userId) {
    global $database;
    
    switch ($userType) {
        case USER_TYPE_ADMIN:
            return true; // Admins can access all projects
            
        case USER_TYPE_MENTOR:
            return $database->exists('project_mentors', 
                'project_id = ? AND mentor_id = ? AND is_active = 1', 
                [$projectId, $userId]
            );
            
        case USER_TYPE_PROJECT:
            return $projectId == $userId; // Project users can only access their own project
            
        default:
            return false;
    }
}

/**
 * Get project statistics for dashboards
 */
function getProjectStatistics($projectId = null) {
    global $database;
    
    if ($projectId) {
        // Individual project stats
        return [
            'metrics' => getProjectMetrics($projectId),
            'team' => getProjectTeam($projectId),
            'mentors' => getProjectMentors($projectId),
            'consensus' => checkMentorConsensusForStageProgression($projectId, 
                $database->getValue("SELECT current_stage FROM projects WHERE project_id = ?", [$projectId])
            )
        ];
    } else {
        // System-wide stats
        return [
            'stage_progression' => getStageProgressionStats(),
            'moderation' => getPendingModerationCounts(),
            'activity' => [
                'total_projects' => $database->count('projects'),
                'active_projects' => $database->count('projects', 'status = "active"'),
                'total_mentors' => $database->count('mentors', 'is_active = 1'),
                'total_innovators' => $database->count('project_innovators', 'is_active = 1')
            ]
        ];
    }
}

// Backward compatibility - keep existing function names
function getProjectProgress($projectId) {
    $project = getProjectById($projectId);
    return $project ? $project['stage_progress'] : 0;
}

function isProjectCompleted($projectId) {
    $project = getProjectById($projectId);
    return $project && ($project['status'] === 'completed' || $project['current_stage'] >= 6);
}

function getMentorProjects($mentorId) {
    global $database;
    
    return $database->getRows("
        SELECT p.*, pm.assigned_at,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE pm.mentor_id = ? AND pm.is_active = 1 AND p.status = 'active'
        ORDER BY pm.assigned_at DESC
    ", [$mentorId]);
}

// Initialize mentor approvals for existing projects (migration helper)
function initializeMentorApprovals() {
    global $database;
    
    try {
        $database->query("
            INSERT IGNORE INTO mentor_stage_approvals 
            (project_id, mentor_id, current_stage, approved_for_next_stage)
            SELECT p.project_id, pm.mentor_id, p.current_stage, 0
            FROM projects p
            INNER JOIN project_mentors pm ON p.project_id = pm.project_id
            WHERE pm.is_active = 1 AND p.status = 'active'
        ");
        
        return true;
    } catch (Exception $e) {
        error_log("Error initializing mentor approvals: " . $e->getMessage());
        return false;
    }
}

?>