<?php
// includes/mentor-consensus-functions.php
// Functions for handling mentor consensus-based stage progression and comment moderation

/**
 * Check if all mentors have approved progression to next stage
 * @param int $projectId The project ID
 * @param int $currentStage The current stage
 * @return array Result with approval status and details
 */
function checkMentorConsensusForStageProgression($projectId, $currentStage) {
    global $database;
    
    // Get all active mentors for this project
    $activeMentors = $database->getRows("
        SELECT pm.mentor_id, m.name, m.email
        FROM project_mentors pm
        INNER JOIN mentors m ON pm.mentor_id = m.mentor_id
        WHERE pm.project_id = ? AND pm.is_active = 1
    ", [$projectId]);
    
    if (empty($activeMentors)) {
        return [
            'can_progress' => false,
            'reason' => 'No active mentors assigned to project',
            'total_mentors' => 0,
            'approved_mentors' => 0,
            'pending_mentors' => []
        ];
    }
    
    // Get mentor approvals for current stage
    $approvals = $database->getRows("
        SELECT msa.mentor_id, msa.approved_for_next_stage, msa.approval_date, m.name
        FROM mentor_stage_approvals msa
        INNER JOIN mentors m ON msa.mentor_id = m.mentor_id
        WHERE msa.project_id = ? AND msa.current_stage = ?
    ", [$projectId, $currentStage]);
    
    $approvalMap = [];
    foreach ($approvals as $approval) {
        $approvalMap[$approval['mentor_id']] = $approval;
    }
    
    $totalMentors = count($activeMentors);
    $approvedCount = 0;
    $pendingMentors = [];
    
    foreach ($activeMentors as $mentor) {
        $mentorId = $mentor['mentor_id'];
        if (isset($approvalMap[$mentorId]) && $approvalMap[$mentorId]['approved_for_next_stage']) {
            $approvedCount++;
        } else {
            $pendingMentors[] = [
                'mentor_id' => $mentorId,
                'name' => $mentor['name'],
                'email' => $mentor['email']
            ];
        }
    }
    
    $canProgress = ($approvedCount === $totalMentors) && ($totalMentors > 0);
    
    return [
        'can_progress' => $canProgress,
        'reason' => $canProgress ? 'All mentors approved' : "Waiting for {$pendingMentors} mentor(s) approval",
        'total_mentors' => $totalMentors,
        'approved_mentors' => $approvedCount,
        'pending_mentors' => $pendingMentors
    ];
}

/**
 * Record mentor's approval for stage progression
 * @param int $projectId The project ID
 * @param int $mentorId The mentor ID
 * @param int $currentStage The current stage
 * @param bool $approved Whether mentor approves progression
 * @return bool Success status
 */
function recordMentorStageApproval($projectId, $mentorId, $currentStage, $approved = true) {
    global $database;
    
    try {
        // Verify mentor is assigned to project
        $assignment = $database->getRow("
            SELECT * FROM project_mentors 
            WHERE project_id = ? AND mentor_id = ? AND is_active = 1
        ", [$projectId, $mentorId]);
        
        if (!$assignment) {
            throw new Exception("Mentor not assigned to project");
        }
        
        // Update or create approval record
        $approvalData = [
            'project_id' => $projectId,
            'mentor_id' => $mentorId,
            'current_stage' => $currentStage,
            'approved_for_next_stage' => $approved ? 1 : 0,
            'approval_date' => $approved ? date('Y-m-d H:i:s') : null
        ];
        
        $existing = $database->getRow("
            SELECT approval_id FROM mentor_stage_approvals
            WHERE project_id = ? AND mentor_id = ? AND current_stage = ?
        ", [$projectId, $mentorId, $currentStage]);
        
        if ($existing) {
            $result = $database->update(
                'mentor_stage_approvals',
                [
                    'approved_for_next_stage' => $approved ? 1 : 0,
                    'approval_date' => $approved ? date('Y-m-d H:i:s') : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'approval_id = ?',
                [$existing['approval_id']]
            );
        } else {
            $result = $database->insert('mentor_stage_approvals', $approvalData);
        }
        
        if ($result) {
            // Log the approval
            logActivity(
                'mentor',
                $mentorId,
                'stage_approval',
                $approved ? "Approved progression from stage {$currentStage}" : "Rejected progression from stage {$currentStage}",
                $projectId
            );
        }
        
        return (bool)$result;
        
    } catch (Exception $e) {
        error_log("Error recording mentor stage approval: " . $e->getMessage());
        return false;
    }
}

/**
 * Attempt to progress project to next stage with mentor consensus
 * @param int $projectId The project ID
 * @param int $mentorId The requesting mentor ID (if applicable)
 * @param bool $isAdminOverride Whether this is an admin override
 * @return array Result with success status and message
 */
function progressProjectStageWithConsensus($projectId, $mentorId = null, $isAdminOverride = false) {
    global $database;
    
    try {
        // Get current project stage
        $project = $database->getRow("
            SELECT project_id, current_stage, project_name, status 
            FROM projects 
            WHERE project_id = ?
        ", [$projectId]);
        
        if (!$project) {
            throw new Exception("Project not found");
        }
        
        if ($project['status'] !== 'active') {
            throw new Exception("Cannot progress inactive project");
        }
        
        $currentStage = $project['current_stage'];
        $nextStage = $currentStage + 1;
        
        if ($nextStage > 6) {
            throw new Exception("Project is already at the final stage");
        }
        
        // Admin can override mentor consensus
        if ($isAdminOverride) {
            return executeStageProgression($projectId, $currentStage, $nextStage, 'Admin override');
        }
        
        // For mentors, first record their approval
        if ($mentorId) {
            recordMentorStageApproval($projectId, $mentorId, $currentStage, true);
        }
        
        // Check if consensus is reached
        $consensus = checkMentorConsensusForStageProgression($projectId, $currentStage);
        
        if ($consensus['can_progress']) {
            return executeStageProgression($projectId, $currentStage, $nextStage, 'Mentor consensus reached');
        } else {
            return [
                'success' => false,
                'message' => 'Your approval has been recorded. ' . $consensus['reason'],
                'consensus_status' => $consensus
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Execute the actual stage progression
 * @param int $projectId
 * @param int $oldStage
 * @param int $newStage
 * @param string $reason
 * @return array
 */
function executeStageProgression($projectId, $oldStage, $newStage, $reason) {
    global $database;
    
    try {
        $database->beginTransaction();
        
        $updateData = ['current_stage' => $newStage];
        
        // Mark as completed if reaching final stage
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
        
        if (!$updated) {
            throw new Exception("Failed to update project stage");
        }
        
        // Log the progression
        logActivity(
            'system',
            null,
            'stage_updated',
            "Project progressed from stage {$oldStage} to {$newStage}. Reason: {$reason}",
            $projectId,
            ['old_stage' => $oldStage, 'new_stage' => $newStage, 'reason' => $reason]
        );
        
        // Send notification email
        $project = $database->getRow("SELECT project_name, project_lead_email FROM projects WHERE project_id = ?", [$projectId]);
        sendEmailNotification(
            $project['project_lead_email'],
            'Your Project Has Advanced!',
            "Great news! Your project '{$project['project_name']}' has progressed to Stage {$newStage}.\n\nReason: {$reason}\n\nBest regards,\nJHUB AFRICA Team",
            NOTIFY_STAGE_UPDATED
        );
        
        $database->commit();
        
        return [
            'success' => true,
            'message' => "Project successfully advanced to Stage {$newStage}!",
            'new_stage' => $newStage
        ];
        
    } catch (Exception $e) {
        $database->rollBack();
        return [
            'success' => false,
            'message' => "Failed to progress project: " . $e->getMessage()
        ];
    }
}

/**
 * Get mentor approval status for a project stage
 * @param int $projectId
 * @param int $stage
 * @return array
 */
function getMentorApprovalStatus($projectId, $stage) {
    global $database;
    
    $approvals = $database->getRows("
        SELECT msa.*, m.name as mentor_name, m.email as mentor_email
        FROM mentor_stage_approvals msa
        INNER JOIN mentors m ON msa.mentor_id = m.mentor_id
        WHERE msa.project_id = ? AND msa.current_stage = ?
        ORDER BY msa.approval_date DESC, m.name ASC
    ", [$projectId, $stage]);
    
    return $approvals;
}

/**
 * Check if comment requires approval
 * @param string $commenterType
 * @return bool
 */
function commentRequiresApproval($commenterType) {
    // Only public/investor comments require approval
    return $commenterType === 'investor';
}

/**
 * Approve a public comment
 * @param int $commentId
 * @param int $adminId
 * @param string $adminNotes
 * @return bool
 */
function approveComment($commentId, $adminId, $adminNotes = '') {
    global $database;
    
    try {
        $updated = $database->update(
            'comments',
            [
                'is_approved' => 1,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'admin_notes' => $adminNotes
            ],
            'comment_id = ?',
            [$commentId]
        );
        
        if ($updated) {
            // Log the approval
            logActivity(
                'admin',
                $adminId,
                'comment_approved',
                "Approved public comment ID: {$commentId}",
                null,
                ['comment_id' => $commentId, 'admin_notes' => $adminNotes]
            );
        }
        
        return (bool)$updated;
        
    } catch (Exception $e) {
        error_log("Error approving comment: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject a public comment
 * @param int $commentId
 * @param int $adminId
 * @param string $reason
 * @return bool
 */
function rejectComment($commentId, $adminId, $reason = '') {
    global $database;
    
    try {
        $updated = $database->update(
            'comments',
            [
                'is_deleted' => 1,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'admin_notes' => "Rejected: " . $reason
            ],
            'comment_id = ?',
            [$commentId]
        );
        
        if ($updated) {
            // Log the rejection
            logActivity(
                'admin',
                $adminId,
                'comment_rejected',
                "Rejected public comment ID: {$commentId}. Reason: {$reason}",
                null,
                ['comment_id' => $commentId, 'rejection_reason' => $reason]
            );
        }
        
        return (bool)$updated;
        
    } catch (Exception $e) {
        error_log("Error rejecting comment: " . $e->getMessage());
        return false;
    }
}

/**
 * Get pending comments for admin review
 * @param int $limit
 * @return array
 */
function getPendingComments($limit = 50) {
    global $database;
    
    return $database->getRows("
        SELECT c.*, p.project_name
        FROM comments c
        INNER JOIN projects p ON c.project_id = p.project_id
        WHERE c.commenter_type = 'investor' 
        AND c.is_approved = 0 
        AND c.is_deleted = 0
        ORDER BY c.created_at DESC
        LIMIT ?
    ", [$limit]);
}

/**
 * Get visible comments for a project (respects approval and admin privacy settings)
 * @param int $projectId
 * @param string $viewerType Current user type
 * @param int $viewerId Current user ID
 * @return array
 */
function getVisibleProjectComments($projectId, $viewerType = null, $viewerId = null) {
    global $database;
    
    $conditions = ["c.project_id = ?", "c.is_deleted = 0"];
    $params = [$projectId];
    
    // Admin privacy: admins cannot see comments made by other admins
    if ($viewerType === 'admin') {
        $conditions[] = "(c.commenter_type != 'admin' OR c.commenter_id = ?)";
        $params[] = $viewerId;
    }
    
    // Comment approval: only show approved public comments
    $conditions[] = "(c.commenter_type != 'investor' OR c.is_approved = 1)";
    
    // Build the query
    $whereClause = implode(' AND ', $conditions);
    
    return $database->getRows("
        SELECT c.*, 
               CASE 
                   WHEN c.commenter_type = 'admin' THEN 'Administrator'
                   WHEN c.commenter_type = 'mentor' THEN 'Mentor'
                   WHEN c.commenter_type = 'innovator' THEN 'Project Team'
                   ELSE c.commenter_name
               END as display_name
        FROM comments c
        WHERE {$whereClause}
        AND c.parent_comment_id IS NULL
        ORDER BY c.created_at DESC
    ", $params);
}
?>