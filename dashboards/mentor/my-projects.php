<?php
/**
 * dashboards/mentor/my-projects.php
 * COMPLETE FILE - Mentor Projects Dashboard with Stage Approval System
 * 
 * Features:
 * - View all assigned projects
 * - View individual project details
 * - Vote on stage progression (approval system)
 * - Track consensus among mentors
 * - Manage resources, assessments, and learning objectives
 */

require_once '../../includes/init.php';

// Require mentor authentication
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
        
        if ($viewProject) {
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
            
            // Get resources for this project
            $projectResources = $database->getRows("
                SELECT mr.*, m.name as mentor_name
                FROM mentor_resources mr
                INNER JOIN mentors m ON mr.mentor_id = m.mentor_id
                WHERE mr.project_id = ? AND mr.is_deleted = 0
                ORDER BY mr.created_at DESC
            ", [$projectId]);
            
            // Get assessments
            $assessments = $database->getRows("
                SELECT * FROM project_assessments
                WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
                ORDER BY created_at DESC
            ", [$projectId, $mentorId]);
            
            // Get learning objectives
            $learningObjectives = $database->getRows("
                SELECT * FROM learning_objectives
                WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
                ORDER BY created_at DESC
            ", [$projectId, $mentorId]);
        }
    }
}

// ============================================
// Handle stage approval voting
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_stage_approval') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $projectId = intval($_POST['project_id']);
        $approve = isset($_POST['approve']); // true if approving, false if withdrawing
        
        // Verify mentor is assigned to this project
        $assignment = $database->getRow(
            "SELECT * FROM project_mentors WHERE project_id = ? AND mentor_id = ? AND is_active = 1",
            [$projectId, $mentorId]
        );
        
        if (!$assignment) {
            $errors[] = 'You are not assigned to this project.';
        } else {
            // Get project details
            $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
            
            if ($project) {
                // Set mentor's approval for next stage
                $updated = setMentorStageApproval(
                    $projectId, 
                    $mentorId, 
                    $project['current_stage'], 
                    $approve
                );
                
                if ($updated) {
                    if ($approve) {
                        // Log the approval
                        logActivity(
                            'mentor', 
                            $mentorId, 
                            'stage_approved', 
                            "Approved progression from Stage {$project['current_stage']} to Stage " . ($project['current_stage'] + 1),
                            $projectId
                        );
                        
                        // Notify other mentors about the approval
                        notifyMentorsAboutApprovalRequest($projectId, $mentorId, $project['current_stage']);
                        
                        // Check if consensus reached and auto-progress stage
                        $progressed = checkAndProgressStage($projectId);
                        
                        if ($progressed) {
                            $success = '✓ All mentors approved! Project has been moved to the next stage.';
                        } else {
                            // Get updated consensus to show in message
                            $consensus = getProjectConsensusStatus($projectId);
                            $success = "✓ Your approval has been recorded. {$consensus['approved_mentors']} of {$consensus['total_mentors']} mentors have approved.";
                        }
                    } else {
                        // Withdrawing approval
                        logActivity(
                            'mentor', 
                            $mentorId, 
                            'stage_approval_withdrawn', 
                            "Withdrew approval for progression from Stage {$project['current_stage']}",
                            $projectId
                        );
                        $success = 'You have withdrawn your approval for stage progression.';
                    }
                    
                    header("Location: my-projects.php?id={$projectId}&msg=" . urlencode($success));
                    exit;
                } else {
                    $errors[] = 'Failed to record your vote. Please try again.';
                }
            } else {
                $errors[] = 'Project not found.';
            }
        }
    }
}

// Get all assigned projects if not viewing specific one
if (!$viewProject) {
    $myProjects = $database->getRows("
        SELECT p.*,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE pm.mentor_id = ? AND pm.is_active = 1
        ORDER BY p.updated_at DESC
    ", [$mentorId]);
}

$pageTitle = $viewProject ? e($viewProject['project_name']) . " - Project Details" : "My Projects";
require_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php if ($viewProject): ?>
                <a href="my-projects.php" class="text-decoration-none text-gray-600">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?php echo e($viewProject['project_name']); ?>
            <?php else: ?>
                <i class="fas fa-briefcase"></i> My Projects
            <?php endif; ?>
        </h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($viewProject): ?>
        <!-- Single Project View -->
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Project Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Project Information</h6>
                        <span class="badge bg-<?php echo $viewProject['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($viewProject['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mt-2"><?php echo nl2br(e($viewProject['description'])); ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Current Stage:</strong>
                                <div class="mt-1">
                                    <span class="badge bg-info fs-6">Stage <?php echo $viewProject['current_stage']; ?> of 6</span>
                                </div>
                            </div>
                        </div>

                        <?php if ($viewProject['project_website']): ?>
                        <div class="mb-3">
                            <strong>Website:</strong>
                            <a href="<?php echo e($viewProject['project_website']); ?>" target="_blank" class="ms-2">
                                <?php echo e($viewProject['project_website']); ?> <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($viewProject['target_market']): ?>
                        <div class="mb-3">
                            <strong>Target Market:</strong>
                            <p class="mt-1"><?php echo e($viewProject['target_market']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <strong>Started:</strong>
                            <span class="ms-2"><?php echo formatDate($viewProject['created_at']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <?php if (!empty($teamMembers)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-users"></i> Team Members (<?php echo count($teamMembers); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($teamMembers as $member): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="<?php echo getGravatar($member['email'], 50); ?>" 
                                     class="rounded-circle me-3" alt="Avatar">
                                <div>
                                    <h6 class="mb-0"><?php echo e($member['name']); ?></h6>
                                    <small class="text-muted"><?php echo e($member['role']); ?></small>
                                    <?php if ($member['level_of_experience']): ?>
                                        <br><small class="text-muted">Experience: <?php echo e($member['level_of_experience']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($member['email']): ?>
                                        <br><small><i class="fas fa-envelope"></i> <?php echo e($member['email']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Resources Section -->
                <?php if (!empty($projectResources)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-book"></i> Shared Resources
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($projectResources as $resource): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <h6><?php echo e($resource['title']); ?></h6>
                                <?php if ($resource['description']): ?>
                                    <p class="text-muted small"><?php echo e($resource['description']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Shared by <?php echo e($resource['mentor_name']); ?> • 
                                        <?php echo formatDate($resource['created_at']); ?>
                                    </small>
                                    <?php if ($resource['resource_url']): ?>
                                        <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- ============================================ -->
                <!-- Stage Management with Approval System -->
                <!-- ============================================ -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-tasks"></i> Stage Management</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get consensus status
                        $consensus = getProjectConsensusStatus($viewProject['project_id']);
                        $myApproval = getMentorApprovalStatus(
                            $viewProject['project_id'], 
                            $mentorId, 
                            $viewProject['current_stage']
                        );
                        $iHaveApproved = $myApproval && $myApproval['approved_for_next_stage'] == 1;
                        ?>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Current Stage</strong>
                                <span class="badge bg-info fs-6">Stage <?php echo $viewProject['current_stage']; ?> of 6</span>
                            </div>
                        </div>
                        
                        <?php if ($viewProject['current_stage'] < 6 && $viewProject['status'] === 'active'): ?>
                            <hr>
                            <h6 class="mb-3"><i class="fas fa-vote-yea"></i> Stage Progression Approval</h6>
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted"><strong>Mentor Approvals</strong></small>
                                    <small><strong><?php echo $consensus['approved_mentors']; ?> / <?php echo $consensus['total_mentors']; ?></strong></small>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <?php 
                                    $percentage = $consensus['total_mentors'] > 0 
                                        ? ($consensus['approved_mentors'] / $consensus['total_mentors']) * 100 
                                        : 0;
                                    $progressColor = $consensus['consensus_reached'] ? 'success' : 'warning';
                                    ?>
                                    <div class="progress-bar bg-<?php echo $progressColor; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo round($percentage); ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Consensus Status -->
                            <?php if ($consensus['consensus_reached']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <strong>Consensus Reached!</strong>
                                    <p class="mb-0 small mt-1">The project will progress to Stage <?php echo ($viewProject['current_stage'] + 1); ?> automatically.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong><?php echo $consensus['approved_mentors']; ?> of <?php echo $consensus['total_mentors']; ?> mentors</strong> have approved moving to Stage <?php echo ($viewProject['current_stage'] + 1); ?>.
                                </div>
                            <?php endif; ?>
                            
                            <!-- Voting Form -->
                            <form method="POST" class="mb-3">
                                <?php echo Validator::csrfInput(); ?>
                                <input type="hidden" name="action" value="vote_stage_approval">
                                <input type="hidden" name="project_id" value="<?php echo $viewProject['project_id']; ?>">
                                
                                <?php if ($iHaveApproved): ?>
                                    <div class="alert alert-success mb-3">
                                        <i class="fas fa-check-circle"></i> <strong>You have approved</strong> progression to Stage <?php echo ($viewProject['current_stage'] + 1); ?>
                                        <?php if ($myApproval['approval_date']): ?>
                                            <br><small class="text-muted">Approved on <?php echo formatDate($myApproval['approval_date']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-times"></i> Withdraw My Approval
                                    </button>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-clock"></i> You have not yet approved progression to the next stage
                                    </div>
                                    <button type="submit" name="approve" value="1" class="btn btn-success w-100">
                                        <i class="fas fa-check"></i> Approve Stage Progression
                                    </button>
                                <?php endif; ?>
                            </form>
                            
                            <!-- Show other mentors' approval status -->
                            <?php if (count($otherMentors) > 1): ?>
                                <hr>
                                <h6 class="mb-2 text-muted">Other Mentors Status</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($otherMentors as $om): ?>
                                        <?php if ($om['mentor_id'] != $mentorId): ?>
                                            <?php 
                                            $omApproval = getMentorApprovalStatus(
                                                $viewProject['project_id'], 
                                                $om['mentor_id'], 
                                                $viewProject['current_stage']
                                            );
                                            $hasApproved = $omApproval && $omApproval['approved_for_next_stage'] == 1;
                                            ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <span><?php echo e($om['name']); ?></span>
                                                <?php if ($hasApproved): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><i class="fas fa-clock"></i> Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($viewProject['current_stage'] == 6): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-trophy"></i> <strong>Project Completed!</strong>
                                <p class="mb-0 mt-2">This project has reached Stage 6 and is complete.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Mentors Card -->
                <?php if (!empty($otherMentors)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user-tie"></i> Project Mentors (<?php echo count($otherMentors); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($otherMentors as $mentor): ?>
                            <div class="mb-3 pb-3 <?php echo $mentor !== end($otherMentors) ? 'border-bottom' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo getGravatar($mentor['email'], 40); ?>" 
                                         class="rounded-circle me-2" alt="Avatar">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <?php echo e($mentor['name']); ?>
                                            <?php if ($mentor['mentor_id'] == $mentorId): ?>
                                                <span class="badge bg-primary">You</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small>
                                        <br><small class="text-muted">
                                            <i class="fas fa-calendar"></i> Joined <?php echo formatDate($mentor['assigned_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="resources.php?project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-book"></i> Add Resource
                            </a>
                            <a href="assessments.php?project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-clipboard-check"></i> Create Assessment
                            </a>
                            <a href="learning-objectives.php?project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-graduation-cap"></i> Add Learning Objective
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Projects List View -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list"></i> All My Projects
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($myProjects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h5>No Projects Assigned</h5>
                        <p class="text-muted">You haven't been assigned to any projects yet.</p>
                        <a href="projects.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Available Projects
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project Name</th>
                                    <th>Stage</th>
                                    <th>Status</th>
                                    <th>Team Size</th>
                                    <th>Mentors</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myProjects as $project): ?>
                                    <?php 
                                    // Get consensus for each project
                                    $projectConsensus = getProjectConsensusStatus($project['project_id']);
                                    $needsMyApproval = false;
                                    if ($projectConsensus && !$projectConsensus['consensus_reached']) {
                                        $myApprovalCheck = getMentorApprovalStatus($project['project_id'], $mentorId, $project['current_stage']);
                                        $needsMyApproval = !$myApprovalCheck || $myApprovalCheck['approved_for_next_stage'] == 0;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($project['project_name']); ?></strong>
                                            <?php if ($needsMyApproval): ?>
                                                <br><small class="badge bg-warning text-dark">
                                                    <i class="fas fa-exclamation-circle"></i> Approval Needed
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">Stage <?php echo $project['current_stage']; ?></span>
                                            <?php if ($projectConsensus && $projectConsensus['total_mentors'] > 1): ?>
                                                <br><small class="text-muted">
                                                    <?php echo $projectConsensus['approved_mentors']; ?>/<?php echo $projectConsensus['total_mentors']; ?> approved
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-users"></i> <?php echo $project['team_count']; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-tie"></i> <?php echo $project['mentor_count']; ?>
                                        </td>
                                        <td><?php echo formatDate($project['updated_at']); ?></td>
                                        <td>
                                            <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>