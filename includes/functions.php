<?php
/**
 * includes/functions.php
 * MERGED VERSION - Core helper functions for JHUB AFRICA Project Tracker
 * Includes Stage Approval System and all common functions
 */

// Initialize global database instance
global $database;
if (!isset($database)) {
    $database = Database::getInstance();
}

// ============================================
// STAGE HELPER FUNCTIONS
// ============================================

/**
 * Calculate stage progress percentage
 */
function getStageProgress($stage) {
    $progressMap = [
        1 => 0,    // Just started
        2 => 17,   // Mentorship begun
        3 => 33,   // Assessment phase
        4 => 50,   // Learning & development
        5 => 67,   // Progress tracking
        6 => 100   // Showcase & integration
    ];
    return $progressMap[$stage] ?? 0;
}

/**
 * Get stage name
 */
function getStageName($stage) {
    $stageNames = [
        1 => 'Project Activation & Setup',
        2 => 'Mentorship & Strategic Planning',
        3 => 'Capacity Building & Skill Development',
        4 => 'Product Development & Incubation',
        5 => 'Progress Evaluation & Showcase',
        6 => 'Integration, Scale-Up & Alumni Transition'
    ];
    return $stageNames[$stage] ?? 'Unknown Stage';
}

/**
 * Get stage description
 */
function getStageDescription($stage) {
    $descriptions = [
        1 => 'Initial project setup, team formation, and activation of resources',
        2 => 'Mentor assignment, strategic guidance, and planning for project execution',
        3 => 'Building team capabilities through targeted skill development and training',
        4 => 'Product/service development, testing, iteration, and incubation support',
        5 => 'Progress monitoring, evaluation, feedback collection, and showcase preparation',
        6 => 'Final showcase, ecosystem integration, scaling strategies, and alumni network transition'
    ];
    return $descriptions[$stage] ?? '';
}

// ============================================
// PROJECT FUNCTIONS
// ============================================

/**
 * Get project by ID with full details
 */
function getProjectById($projectId) {
    global $database;
    return $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
}

/**
 * Get all active projects
 */
function getAllActiveProjects() {
    global $database;
    return $database->getRows("SELECT * FROM projects WHERE status = 'active' ORDER BY created_at DESC", []);
}

/**
 * Get all projects for a user
 */
function getUserProjects($userType, $userId) {
    global $database;
    
    switch ($userType) {
        case 'admin':
            return $database->getRows("
                SELECT p.*, 
                       COUNT(DISTINCT pi.pi_id) as innovator_count,
                       COUNT(DISTINCT pm.mentor_id) as mentor_count
                FROM projects p
                LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
                LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
                GROUP BY p.project_id
                ORDER BY p.created_at DESC
            ");
            
        case 'mentor':
            return $database->getRows("
                SELECT p.*, pm.assigned_at,
                       COUNT(DISTINCT pi.pi_id) as innovator_count
                FROM projects p
                INNER JOIN project_mentors pm ON p.project_id = pm.project_id
                LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
                WHERE pm.mentor_id = ? AND pm.is_active = 1 AND p.status != 'terminated'
                GROUP BY p.project_id
                ORDER BY pm.assigned_at DESC
            ", [$userId]);
            
        case 'project':
            return $database->getRows("
                SELECT p.*,
                       COUNT(DISTINCT pi.pi_id) as innovator_count,
                       COUNT(DISTINCT pm.mentor_id) as mentor_count
                FROM projects p
                LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
                LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
                WHERE p.project_id = ?
                GROUP BY p.project_id
            ", [$userId]);
    }
    
    return [];
}

/**
 * Get project team members
 */
function getProjectTeam($projectId) {
    global $database;
    return $database->getRows("
        SELECT * FROM project_innovators 
        WHERE project_id = ? AND is_active = 1 
        ORDER BY added_at ASC
    ", [$projectId]);
}

/**
 * Get project mentors
 */
function getProjectMentors($projectId) {
    global $database;
    return $database->getRows("
        SELECT m.*, pm.assigned_at, pm.notes
        FROM project_mentors pm
        INNER JOIN mentors m ON pm.mentor_id = m.mentor_id
        WHERE pm.project_id = ? AND pm.is_active = 1 AND m.is_active = 1
        ORDER BY pm.assigned_at ASC
    ", [$projectId]);
}

/**
 * Get mentor by ID
 */
function getMentorById($mentorId) {
    global $database;
    return $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);
}

/**
 * Get project statistics
 */
function getProjectStats($projectId) {
    global $database;
    
    return [
        'team_count' => $database->count('project_innovators', 'project_id = ? AND is_active = 1', [$projectId]),
        'mentor_count' => $database->count('project_mentors', 'project_id = ? AND is_active = 1', [$projectId]),
        'resource_count' => $database->count('mentor_resources', 'project_id = ? AND is_deleted = 0', [$projectId]),
        'assessment_count' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0', [$projectId])
    ];
}

/**
 * Get project comments
 */
function getProjectComments($projectId, $parentId = null, $limit = 50) {
    global $database;
    
    $sql = "
        SELECT * FROM comments 
        WHERE project_id = ? AND parent_comment_id " . ($parentId ? "= ?" : "IS NULL") . " 
        AND is_deleted = 0
        ORDER BY created_at ASC
    ";
    
    $params = $parentId ? [$projectId, $parentId] : [$projectId];
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    return $database->getRows($sql, $params);
}

// ============================================
// MENTOR FUNCTIONS
// ============================================

/**
 * Check if mentor is assigned to project
 */
function isMentorAssignedToProject($mentorId, $projectId) {
    global $database;
    $result = $database->getRow("
        SELECT pm_id FROM project_mentors 
        WHERE mentor_id = ? AND project_id = ? AND is_active = 1
    ", [$mentorId, $projectId]);
    
    return !empty($result);
}

/**
 * Get available projects for mentor assignment
 */
function getAvailableProjectsForMentor($mentorId) {
    global $database;
    return $database->getRows("
        SELECT p.*, 
               COUNT(DISTINCT pi.pi_id) as innovator_count,
               COUNT(DISTINCT pm.mentor_id) as mentor_count
        FROM projects p
        LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
        LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
        WHERE p.status = 'active' 
        AND p.project_id NOT IN (
            SELECT project_id FROM project_mentors 
            WHERE mentor_id = ? AND is_active = 1
        )
        GROUP BY p.project_id
        ORDER BY p.created_at DESC
    ", [$mentorId]);
}

// ============================================
// STATISTICS & DASHBOARD FUNCTIONS
// ============================================

/**
 * Get system statistics for admin dashboard
 */
function getSystemStatistics() {
    global $database;
    
    $stats = [];
    
    // Total projects
    $stats['total_projects'] = $database->count('projects');
    $stats['active_projects'] = $database->count('projects', 'status = ?', ['active']);
    $stats['completed_projects'] = $database->count('projects', 'status = ?', ['completed']);
    $stats['terminated_projects'] = $database->count('projects', 'status = ?', ['terminated']);
    
    // Applications
    $stats['pending_applications'] = $database->count('project_applications', 'status = ?', ['pending']);
    $stats['total_applications'] = $database->count('project_applications');
    
    // Users
    $stats['total_mentors'] = $database->count('mentors', 'is_active = 1');
    $stats['total_innovators'] = $database->count('project_innovators', 'is_active = 1');
    
    // Recent activity
    $stats['projects_this_month'] = $database->count('projects', 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)');
    $stats['applications_this_week'] = $database->count('project_applications', 'applied_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
    
    return $stats;
}

/**
 * Get system statistics (alias for compatibility)
 */
function getSystemStats() {
    return getSystemStatistics();
}

/**
 * Get projects by stage distribution
 */
function getProjectsByStage() {
    global $database;
    
    $stages = [];
    for ($i = 1; $i <= 6; $i++) {
        $stages[$i] = $database->count('projects', 'current_stage = ? AND status = ?', [$i, 'active']);
    }
    
    return $stages;
}

// ============================================
// ACTIVITY & LOGGING FUNCTIONS
// ============================================

/**
 * Log activity to activity_logs table
 */
function logActivity($userType, $userId, $action, $description, $projectId = null, $additionalData = null) {
    global $database;
    
    $data = [
        'user_type' => $userType,
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    if ($projectId) {
        $data['project_id'] = $projectId;
        $data['target_id'] = $projectId; // For compatibility
    }
    
    if ($additionalData) {
        $data['additional_data'] = json_encode($additionalData);
    }
    
    return $database->insert('activity_logs', $data);
}

/**
 * Get recent activity logs
 */
function getRecentActivity($limit = 20, $userType = null, $userId = null) {
    global $database;
    
    $sql = "SELECT * FROM activity_logs WHERE 1=1";
    $params = [];
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
    }
    
    if ($userId) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return $database->getRows($sql, $params);
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Send email notification
 */
function sendEmailNotification($to, $subject, $message, $type = 'general', $attachments = [], $relatedProjectId = null) {
    global $database;
    
    // Try to use EmailService if available
    if (class_exists('EmailService')) {
        try {
            $emailService = new EmailService();
            return $emailService->sendEmail($to, $subject, $message, $type, $attachments);
        } catch (Exception $e) {
            error_log("Failed to send email via EmailService: " . $e->getMessage());
            // Fall through to queue method
        }
    }
    
    // Fallback: Queue the email
    $recipientName = strstr($to, '@', true);
    
    $data = [
        'recipient_email' => $to,
        'recipient_name' => $recipientName,
        'subject' => $subject,
        'message_body' => $message,
        'notification_type' => $type,
        'related_project_id' => $relatedProjectId,
        'status' => 'pending'
    ];
    
    return $database->insert('email_notifications', $data);
}

/**
 * Send templated email notification
 */
function sendTemplateEmail($to, $type, $data = []) {
    if (!class_exists('EmailService')) {
        error_log("EmailService class not found for template email");
        return false;
    }
    
    try {
        $emailService = new EmailService();
        return $emailService->sendTemplateEmail($to, $type, $data);
    } catch (Exception $e) {
        error_log("Failed to send template email: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification for user
 */
function createNotification($userId, $userType, $title, $message, $notificationType = 'info', $actionUrl = null, $metadata = null) {
    global $database;
    
    $data = [
        'user_id' => $userId,
        'user_type' => $userType,
        'title' => $title,
        'message' => $message,
        'notification_type' => $notificationType,
        'action_url' => $actionUrl,
        'metadata' => $metadata ? json_encode($metadata) : null
    ];
    
    return $database->insert('notifications', $data);
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($userId, $userType) {
    global $database;
    
    return $database->count(
        'notifications',
        'user_id = ? AND user_type = ? AND is_read = 0',
        [$userId, $userType]
    );
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $userType, $limit = 10) {
    global $database;
    
    return $database->getRows(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND user_type = ? 
         ORDER BY created_at DESC 
         LIMIT ?",
        [$userId, $userType, $limit]
    );
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId) {
    global $database;
    
    return $database->update(
        'notifications',
        ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
        'notification_id = ?',
        [$notificationId]
    );
}

// ============================================
// USER & PERMISSION FUNCTIONS
// ============================================

/**
 * Check if user is assigned to project
 */
function isUserAssignedToProject($projectId, $userId, $userType) {
    global $database;
    
    if ($userType === 'mentor') {
        $assignment = $database->getRow(
            "SELECT * FROM project_mentors WHERE project_id = ? AND mentor_id = ? AND is_active = 1",
            [$projectId, $userId]
        );
        return !empty($assignment);
    }
    
    if ($userType === 'project') {
        $project = $database->getRow(
            "SELECT * FROM projects WHERE project_id = ?",
            [$projectId]
        );
        return !empty($project);
    }
    
    return false;
}

// ============================================
// STAGE APPROVAL SYSTEM FUNCTIONS
// ============================================

/**
 * Get consensus status for a project using the existing view
 * 
 * @param int $projectId
 * @return array|null Consensus data including total_mentors, approved_mentors, consensus_reached
 */
function getProjectConsensusStatus($projectId) {
    global $database;
    
    $status = $database->getRow(
        "SELECT * FROM v_project_consensus_status WHERE project_id = ?",
        [$projectId]
    );
    
    return $status;
}

/**
 * Set or update a mentor's approval for stage progression
 * 
 * @param int $projectId
 * @param int $mentorId
 * @param int $currentStage
 * @param bool $approved
 * @return bool Success status
 */
function setMentorStageApproval($projectId, $mentorId, $currentStage, $approved = true) {
    global $database;
    
    // Check if record exists
    $existing = $database->getRow(
        "SELECT * FROM mentor_stage_approvals 
         WHERE project_id = ? AND mentor_id = ? AND current_stage = ?",
        [$projectId, $mentorId, $currentStage]
    );
    
    if ($existing) {
        // Update existing approval
        $updated = $database->update(
            'mentor_stage_approvals',
            [
                'approved_for_next_stage' => $approved ? 1 : 0,
                'approval_date' => $approved ? date('Y-m-d H:i:s') : null
            ],
            'approval_id = ?',
            [$existing['approval_id']]
        );
        
        return $updated !== false;
    } else {
        // Insert new approval record
        $data = [
            'project_id' => $projectId,
            'mentor_id' => $mentorId,
            'current_stage' => $currentStage,
            'approved_for_next_stage' => $approved ? 1 : 0,
            'approval_date' => $approved ? date('Y-m-d H:i:s') : null
        ];
        
        $insertId = $database->insert('mentor_stage_approvals', $data);
        return $insertId !== false;
    }
}

/**
 * Check if consensus is reached and automatically progress stage
 * 
 * @param int $projectId
 * @return bool True if stage was updated, false otherwise
 */
function checkAndProgressStage($projectId) {
    global $database;
    
    $consensus = getProjectConsensusStatus($projectId);
    
    if (!$consensus) {
        return false;
    }
    
    // Check if consensus reached and there are mentors
    if ($consensus['consensus_reached'] == 1 && $consensus['total_mentors'] > 0) {
        $newStage = $consensus['current_stage'] + 1;
        
        // Don't exceed stage 6
        if ($newStage > 6) {
            return false;
        }
        
        // Prepare update data
        $updateData = ['current_stage' => $newStage];
        
        // Mark as completed if reaching stage 6
        if ($newStage == 6) {
            $updateData['status'] = 'completed';
            $updateData['completion_date'] = date('Y-m-d H:i:s');
        }
        
        // Update project stage
        $updated = $database->update(
            'projects',
            $updateData,
            'project_id = ?',
            [$projectId]
        );
        
        if ($updated) {
            // Reset all mentor approvals for the new stage
            resetMentorApprovalsForStage($projectId, $newStage);
            
            // Log the automatic progression
            logActivity(
                'system',
                null,
                'stage_auto_progressed',
                "Project automatically progressed to stage {$newStage} after all mentors approved",
                $projectId,
                [
                    'old_stage' => $consensus['current_stage'],
                    'new_stage' => $newStage,
                    'approved_mentors' => $consensus['approved_mentors'],
                    'total_mentors' => $consensus['total_mentors']
                ]
            );
            
            // Notify project lead
            $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
            if ($project) {
                sendEmailNotification(
                    $project['project_lead_email'],
                    'Project Stage Updated - ' . $project['project_name'],
                    "Great news! Your project '{$project['project_name']}' has progressed to Stage {$newStage} after receiving approval from all mentors.\n\nBest regards,\nJHUB AFRICA Team",
                    'stage_updated',
                    [],
                    $projectId
                );
            }
            
            return true;
        }
    }
    
    return false;
}

/**
 * Reset/create mentor approvals when stage changes
 * Creates new approval records for all active mentors at the new stage
 * 
 * @param int $projectId
 * @param int $newStage
 * @return void
 */
function resetMentorApprovalsForStage($projectId, $newStage) {
    global $database;
    
    // Get all active mentors for this project
    $mentors = $database->getRows(
        "SELECT mentor_id FROM project_mentors 
         WHERE project_id = ? AND is_active = 1",
        [$projectId]
    );
    
    foreach ($mentors as $mentor) {
        // Check if approval record already exists for this stage
        $existing = $database->getRow(
            "SELECT * FROM mentor_stage_approvals 
             WHERE project_id = ? AND mentor_id = ? AND current_stage = ?",
            [$projectId, $mentor['mentor_id'], $newStage]
        );
        
        if (!$existing) {
            // Create approval record for new stage (default: not approved)
            $data = [
                'project_id' => $projectId,
                'mentor_id' => $mentor['mentor_id'],
                'current_stage' => $newStage,
                'approved_for_next_stage' => 0,
                'approval_date' => null
            ];
            
            $database->insert('mentor_stage_approvals', $data);
        }
    }
}

/**
 * Get a specific mentor's approval status for current stage
 * 
 * @param int $projectId
 * @param int $mentorId
 * @param int $currentStage
 * @return array|null Approval record or null
 */
function getMentorApprovalStatus($projectId, $mentorId, $currentStage) {
    global $database;
    
    return $database->getRow(
        "SELECT * FROM mentor_stage_approvals 
         WHERE project_id = ? AND mentor_id = ? AND current_stage = ?",
        [$projectId, $mentorId, $currentStage]
    );
}

/**
 * Notify all project mentors about a stage approval request
 * 
 * @param int $projectId
 * @param int $initiatingMentorId
 * @param int $currentStage
 * @return void
 */
function notifyMentorsAboutApprovalRequest($projectId, $initiatingMentorId, $currentStage) {
    global $database;
    
    $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
    $initiator = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$initiatingMentorId]);
    
    if (!$project || !$initiator) {
        return;
    }
    
    // Get all other mentors on the project
    $mentors = $database->getRows(
        "SELECT m.* FROM mentors m
         INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
         WHERE pm.project_id = ? AND pm.is_active = 1 AND m.mentor_id != ?",
        [$projectId, $initiatingMentorId]
    );
    
    foreach ($mentors as $mentor) {
        // Create notification
        createNotification(
            $mentor['mentor_id'],
            'mentor',
            'Stage Approval Request',
            "{$initiator['name']} has approved moving '{$project['project_name']}' from Stage {$currentStage} to Stage " . ($currentStage + 1) . ". Your approval is needed.",
            'warning',
            "/dashboards/mentor/my-projects.php?id={$projectId}",
            [
                'project_id' => $projectId,
                'current_stage' => $currentStage,
                'initiating_mentor' => $initiatingMentorId
            ]
        );
        
        // Queue email notification
        sendEmailNotification(
            $mentor['email'],
            'Stage Approval Request - ' . $project['project_name'],
            "Hi {$mentor['name']},\n\n{$initiator['name']} has approved moving the project '{$project['project_name']}' from Stage {$currentStage} to Stage " . ($currentStage + 1) . ".\n\nYour approval is needed for the project to progress. Please log in to review and approve.\n\nBest regards,\nJHUB AFRICA Team",
            'stage_updated',
            [],
            $projectId
        );
    }
}

/**
 * Create initial approval records when a mentor is assigned to a project
 * 
 * @param int $projectId
 * @param int $mentorId
 * @return void
 */
function createInitialMentorApproval($projectId, $mentorId) {
    global $database;
    
    $project = $database->getRow("SELECT current_stage FROM projects WHERE project_id = ?", [$projectId]);
    
    if ($project) {
        // Check if approval record already exists
        $existing = $database->getRow(
            "SELECT * FROM mentor_stage_approvals 
             WHERE project_id = ? AND mentor_id = ? AND current_stage = ?",
            [$projectId, $mentorId, $project['current_stage']]
        );
        
        if (!$existing) {
            $approvalData = [
                'project_id' => $projectId,
                'mentor_id' => $mentorId,
                'current_stage' => $project['current_stage'],
                'approved_for_next_stage' => 0,
                'approval_date' => null
            ];
            
            $database->insert('mentor_stage_approvals', $approvalData);
        }
    }
}

/**
 * Get all mentors with their approval status for current stage
 * 
 * @param int $projectId
 * @param int $currentStage
 * @return array List of mentors with approval status
 */
function getMentorsWithApprovalStatus($projectId, $currentStage) {
    global $database;
    
    return $database->getRows(
        "SELECT m.mentor_id, m.name, m.email,
                COALESCE(msa.approved_for_next_stage, 0) as has_approved,
                msa.approval_date
         FROM mentors m
         INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
         LEFT JOIN mentor_stage_approvals msa ON m.mentor_id = msa.mentor_id 
                   AND msa.project_id = pm.project_id 
                   AND msa.current_stage = ?
         WHERE pm.project_id = ? AND pm.is_active = 1
         ORDER BY m.name",
        [$currentStage, $projectId]
    );
}

?>