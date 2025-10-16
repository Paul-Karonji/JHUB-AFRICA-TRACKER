<?php
// dashboards/mentor/my-projects.php - Updated with consensus-based stage progression
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();
$viewProject = null;
$errors = [];
$success = '';

// Check if viewing specific project
if (isset($_GET['id'])) {
    $projectId = intval($_GET['id']);
    
    // Verify mentor is assigned to this project
    $assignment = $database->getRow("
        SELECT * FROM project_mentors 
        WHERE project_id = ? AND mentor_id = ? AND is_active = 1
    ", [$projectId, $mentorId]);
    
    if ($assignment) {
        $viewProject = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
        
        // Get consensus status for current stage
        $consensusStatus = checkMentorConsensusForStageProgression($projectId, $viewProject['current_stage']);
        $mentorApprovals = getMentorApprovalStatus($projectId, $viewProject['current_stage']);
        
        // Check if current mentor has approved
        $currentMentorApproval = null;
        foreach ($mentorApprovals as $approval) {
            if ($approval['mentor_id'] == $mentorId) {
                $currentMentorApproval = $approval;
                break;
            }
        }
        
        // Get project team
        $teamMembers = $database->getRows("
            SELECT * FROM project_innovators 
            WHERE project_id = ? AND is_active = 1
            ORDER BY added_at ASC
        ", [$projectId]);
        
        // Get other mentors
        $otherMentors = $database->getRows("
            SELECT m.*, pm.assigned_at
            FROM mentors m
            INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
            WHERE pm.project_id = ? AND pm.is_active = 1
            ORDER BY pm.assigned_at ASC
        ", [$projectId]);
        
        // Get resources, assessments, etc. (existing code)
        $projectResources = $database->getRows("
            SELECT mr.*, m.name as mentor_name
            FROM mentor_resources mr
            INNER JOIN mentors m ON mr.mentor_id = m.mentor_id
            WHERE mr.project_id = ? AND mr.is_deleted = 0
            ORDER BY mr.created_at DESC
        ", [$projectId]);
        
        $assessments = $database->getRows("
            SELECT * FROM project_assessments
            WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
            ORDER BY created_at DESC
        ", [$projectId, $mentorId]);
        
        $learningObjectives = $database->getRows("
            SELECT * FROM learning_objectives
            WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
            ORDER BY created_at DESC
        ", [$projectId, $mentorId]);
    }
}

// Get all assigned projects if not viewing specific one
if (!$viewProject) {
    $myProjects = $database->getRows("
        SELECT p.*,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count,
               (SELECT COUNT(*) FROM mentor_stage_approvals msa 
                WHERE msa.project_id = p.project_id 
                AND msa.current_stage = p.current_stage 
                AND msa.approved_for_next_stage = 1) as approved_mentors_count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE pm.mentor_id = ? AND pm.is_active = 1
        ORDER BY p.updated_at DESC
    ", [$mentorId]);
    
    // Add consensus info to each project
    foreach ($myProjects as &$project) {
        $project['consensus_status'] = checkMentorConsensusForStageProgression($project['project_id'], $project['current_stage']);
        
        // Check if current mentor has approved this project's current stage
        $myApproval = $database->getRow("
            SELECT * FROM mentor_stage_approvals 
            WHERE project_id = ? AND mentor_id = ? AND current_stage = ?
        ", [$project['project_id'], $mentorId, $project['current_stage']]);
        
        $project['my_approval_status'] = $myApproval ? $myApproval['approved_for_next_stage'] : 0;
    }
}

$pageTitle = $viewProject ? e($viewProject['project_name']) . ' - Project Details' : 'My Projects';
$breadcrumbs = [
    ['title' => 'Mentor Dashboard', 'url' => BASE_URL . '/dashboards/mentor/'],
    ['title' => 'My Projects', 'url' => '']
];

include '../../templates/header.php';
?>

<div class="container-fluid">
    
    <?php if ($viewProject): ?>
        <!-- Single Project View with Consensus Interface -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800"><?php echo e($viewProject['project_name']); ?></h1>
                <p class="mb-0 text-muted">Stage <?php echo $viewProject['current_stage']; ?>: <?php echo getStageName($viewProject['current_stage']); ?></p>
            </div>
            <a href="my-projects.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Projects
            </a>
        </div>

        <!-- Stage Progression Consensus Panel -->
        <div class="card shadow mb-4 border-left-<?php echo $consensusStatus['can_progress'] ? 'success' : 'warning'; ?>">
            <div class="card-header bg-<?php echo $consensusStatus['can_progress'] ? 'success' : 'warning'; ?> text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-users me-2"></i>Stage Progression - Mentor Consensus
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Status</h6>
                        <p class="mb-2">
                            <strong>Stage <?php echo $viewProject['current_stage']; ?>:</strong> 
                            <?php echo getStageName($viewProject['current_stage']); ?>
                        </p>
                        <p class="mb-3 text-muted">
                            <?php echo getStageDescription($viewProject['current_stage']); ?>
                        </p>
                        
                        <div class="consensus-status">
                            <?php if ($consensusStatus['can_progress']): ?>
                                <div class="alert alert-success mb-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Ready to Progress!</strong> All mentors have approved advancement to Stage <?php echo ($viewProject['current_stage'] + 1); ?>.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Awaiting Consensus:</strong> 
                                    <?php echo $consensusStatus['approved_mentors']; ?> of <?php echo $consensusStatus['total_mentors']; ?> mentors have approved progression.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Mentor Approvals</h6>
                        <div class="mentor-approvals">
                            <?php foreach ($mentorApprovals as $approval): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-3">
                                        <?php if ($approval['approved_for_next_stage']): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-clock text-warning"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?php echo e($approval['mentor_name']); ?></strong>
                                        <?php if ($approval['mentor_id'] == $mentorId): ?>
                                            <span class="badge bg-primary ms-2">You</span>
                                        <?php endif; ?>
                                        <div class="small text-muted">
                                            <?php if ($approval['approved_for_next_stage']): ?>
                                                Approved on <?php echo formatDate($approval['approval_date']); ?>
                                            <?php else: ?>
                                                Approval pending
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Approval Actions -->
                        <div class="mt-3">
                            <?php if ($currentMentorApproval && $currentMentorApproval['approved_for_next_stage']): ?>
                                <button type="button" class="btn btn-warning btn-sm" onclick="revokeApproval()">
                                    <i class="fas fa-undo me-2"></i>Revoke My Approval
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success btn-sm" onclick="approveProgression()">
                                    <i class="fas fa-check me-2"></i>Approve Progression to Stage <?php echo ($viewProject['current_stage'] + 1); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($viewProject['current_stage'] < 6): ?>
                    <!-- Next Stage Preview -->
                    <hr>
                    <div class="next-stage-preview">
                        <h6><i class="fas fa-arrow-right me-2"></i>Next Stage: <?php echo getStageName($viewProject['current_stage'] + 1); ?></h6>
                        <p class="text-muted mb-0"><?php echo getStageDescription($viewProject['current_stage'] + 1); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Existing project details sections (team, resources, assessments, etc.) -->
        <div class="row">
            <div class="col-md-4">
                <!-- Project Team -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Project Team</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($teamMembers as $member): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img class="rounded-circle mr-2" src="<?php echo getGravatar($member['email'], 40); ?>" alt="">
                                <div class="ms-3">
                                    <div class="font-weight-bold"><?php echo e($member['name']); ?></div>
                                    <div class="text-muted small"><?php echo e($member['role']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Other Mentors -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Project Mentors</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($otherMentors as $mentor): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img class="rounded-circle mr-2" src="<?php echo getGravatar($mentor['email'], 40); ?>" alt="">
                                <div class="ms-3">
                                    <div class="font-weight-bold">
                                        <?php echo e($mentor['name']); ?>
                                        <?php if ($mentor['mentor_id'] == $mentorId): ?>
                                            <span class="badge bg-primary ms-1">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?php echo e($mentor['area_of_expertise']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Project Description and other details -->
                <!-- (Include existing code for resources, assessments, etc.) -->
            </div>
        </div>
        
    <?php else: ?>
        <!-- Projects List View with Consensus Status -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-project-diagram text-primary me-2"></i>My Projects
            </h1>
        </div>

        <!-- Projects Grid with Consensus Indicators -->
        <div class="row">
            <?php foreach ($myProjects as $project): ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow border-left-<?php echo $project['consensus_status']['can_progress'] ? 'success' : ($project['my_approval_status'] ? 'warning' : 'info'); ?> h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo e($project['project_name']); ?>
                                    </a>
                                </h5>
                                <span class="badge bg-primary">
                                    Stage <?php echo $project['current_stage']; ?>
                                </span>
                            </div>
                            
                            <!-- Consensus Status Indicator -->
                            <div class="consensus-indicator mb-3">
                                <?php if ($project['consensus_status']['can_progress']): ?>
                                    <div class="alert alert-success py-2 mb-2">
                                        <small>
                                            <i class="fas fa-check-circle me-1"></i>
                                            Ready to progress! All mentors approved.
                                        </small>
                                    </div>
                                <?php elseif ($project['my_approval_status']): ?>
                                    <div class="alert alert-warning py-2 mb-2">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            You approved. Waiting for other mentors.
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info py-2 mb-2">
                                        <small>
                                            <i class="fas fa-hand-paper me-1"></i>
                                            Your approval needed for progression.
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="small mb-1">
                                    Progress: <?php echo $project['consensus_status']['approved_mentors']; ?>/<?php echo $project['consensus_status']['total_mentors']; ?> mentors approved
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?php echo $project['consensus_status']['can_progress'] ? 'bg-success' : 'bg-warning'; ?>" 
                                         style="width: <?php echo ($project['consensus_status']['approved_mentors'] / max($project['consensus_status']['total_mentors'], 1)) * 100; ?>%">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Project Stats -->
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="small text-muted">Team</div>
                                    <div class="font-weight-bold"><?php echo $project['team_count']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Mentors</div>
                                    <div class="font-weight-bold"><?php echo $project['mentor_count']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Stage</div>
                                    <div class="font-weight-bold"><?php echo $project['current_stage']; ?>/6</div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="mt-3 d-flex justify-content-between">
                                <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                
                                <?php if (!$project['my_approval_status'] && $project['current_stage'] < 6): ?>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="quickApprove(<?php echo $project['project_id']; ?>)">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($myProjects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                <h5>No Projects Assigned</h5>
                <p class="text-muted">You haven't been assigned to any projects yet.</p>
                <a href="<?php echo BASE_URL; ?>/dashboards/mentor/available-projects.php" 
                   class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Find Projects to Mentor
                </a>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<!-- Alert Area -->
<div id="alertArea"></div>

<script>
// Approve progression for current project
function approveProgression() {
    const projectId = <?php echo $viewProject ? $viewProject['project_id'] : 'null'; ?>;
    
    if (!projectId) return;
    
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        project_id: projectId,
        action: 'approve_progression'
    };
    
    fetch('<?php echo BASE_URL; ?>/api/projects/update-stage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (data.new_stage) {
                setTimeout(() => location.reload(), 2000);
            } else {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showAlert('info', data.message);
            if (data.consensus_status) {
                setTimeout(() => location.reload(), 2000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while processing your approval');
    });
}

// Revoke approval
function revokeApproval() {
    const projectId = <?php echo $viewProject ? $viewProject['project_id'] : 'null'; ?>;
    
    if (!projectId) return;
    
    if (!confirm('Are you sure you want to revoke your approval for stage progression?')) {
        return;
    }
    
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        project_id: projectId,
        action: 'revoke_approval'
    };
    
    fetch('<?php echo BASE_URL; ?>/api/projects/update-stage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while revoking your approval');
    });
}

// Quick approve from projects list
function quickApprove(projectId) {
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        project_id: projectId,
        action: 'approve_progression'
    };
    
    fetch('<?php echo BASE_URL; ?>/api/projects/update-stage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('info', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while processing your approval');
    });
}

// Show alert function
function showAlert(type, message) {
    const alertArea = document.getElementById('alertArea');
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'info' ? 'alert-info' : 'alert-warning';
    
    alertArea.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertArea.querySelector('.alert');
        if (alert && bootstrap.Alert.getInstance(alert)) {
            bootstrap.Alert.getInstance(alert).close();
        }
    }, 5000);
}
</script>

<?php include '../../templates/footer.php'; ?>