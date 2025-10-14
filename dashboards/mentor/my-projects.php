<?php
// dashboards/mentor/my-projects.php
require_once '../../includes/init.php';

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

// Handle stage update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stage') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $projectId = intval($_POST['project_id']);
        $newStage = intval($_POST['current_stage']);
        
        if ($newStage >= 1 && $newStage <= 6) {
            $updateData = ['current_stage' => $newStage];
            
            if ($newStage == 6) {
                $updateData['status'] = 'completed';
                $updateData['completion_date'] = date('Y-m-d H:i:s');
            }
            
            $updated = $database->update('projects', $updateData, 'project_id = ?', [$projectId]);
            
            if ($updated) {
                logActivity('mentor', $mentorId, 'stage_updated', "Updated project stage to {$newStage}", null, ['project_id' => $projectId]);
                $success = 'Project stage updated successfully!';
                header("Location: my-projects.php?id={$projectId}");
                exit;
            } else {
                $errors[] = 'Failed to update project stage';
            }
        } else {
            $errors[] = 'Invalid stage number';
        }
    }
}

// Get all assigned projects if not viewing specific one
if (!$viewProject) {
    $myProjects = $database->getRows("
    
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE pm.mentor_id = ? AND pm.is_active = 1
        ORDER BY p.updated_at DESC
    ", [$mentorId]);
}

$pageTitle = $viewProject ? e($viewProject['project_name']) . " - My Projects" : "My Projects";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <!-- Display errors/success -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($viewProject): ?>
        <!-- Single Project View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="my-projects.php" class="text-decoration-none text-muted mb-2 d-block">
                    <i class="fas fa-arrow-left me-1"></i> Back to My Projects
                </a>
                <h1 class="h3 mb-0"><?php echo e($viewProject['project_name']); ?></h1>
                <p class="text-muted mb-0">Project Lead: <?php echo e($viewProject['project_lead_name']); ?></p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 me-2">Stage <?php echo $viewProject['current_stage']; ?></span>
                <span class="badge bg-<?php echo $viewProject['status'] === 'active' ? 'success' : ($viewProject['status'] === 'completed' ? 'info' : 'danger'); ?> fs-6">
                    <?php echo ucfirst($viewProject['status']); ?>
                </span>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Project Details Card -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Project Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mt-2"><?php echo nl2br(e($viewProject['description'])); ?></p>
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
                        
                        <div class="mb-3">
                            <strong>Lead Email:</strong>
                            <a href="mailto:<?php echo e($viewProject['project_lead_email']); ?>" class="ms-2">
                                <?php echo e($viewProject['project_lead_email']); ?>
                            </a>
                        </div>
                        
                        <div>
                            <strong>Started:</strong>
                            <span class="ms-2"><?php echo formatDate($viewProject['created_at']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Stage Management Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Stage Management</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php echo Validator::csrfInput(); ?>
                            <input type="hidden" name="action" value="update_stage">
                            <input type="hidden" name="project_id" value="<?php echo $viewProject['project_id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Current Stage</label>
                                <select name="current_stage" class="form-select" required>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $viewProject['current_stage'] == $i ? 'selected' : ''; ?>>
                                            Stage <?php echo $i; ?>: <?php 
                                                $stageNames = [
                                                    1 => 'Ideation & Concept Development',
                                                    2 => 'Prototype Development',
                                                    3 => 'Testing & Validation',
                                                    4 => 'Learning & Development',
                                                    5 => 'Progress Tracking',
                                                    6 => 'Showcase & Integration'
                                                ];
                                                echo $stageNames[$i];
                                            ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Stage
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Team Members Card -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Team Members (<?php echo count($teamMembers); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teamMembers)): ?>
                            <p class="text-muted mb-0">No team members added yet.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($teamMembers as $member): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo getGravatar($member['email'], 40); ?>" class="rounded-circle me-3" alt="<?php echo e($member['name']); ?>">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo e($member['name']); ?></h6>
                                            <small class="text-muted"><?php echo e($member['role']); ?> - <?php echo e($member['email']); ?></small>
                                        </div>
                                        <small class="text-muted">Joined <?php echo timeAgo($member['added_at']); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Resources Card -->
                <div class="card shadow mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Resources (<?php echo count($projectResources); ?>)</h5>
                        <a href="resources.php?action=create&project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Resource
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projectResources)): ?>
                            <p class="text-muted mb-0">No resources shared yet.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($projectResources as $resource): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo e($resource['title']); ?></h6>
                                        <small class="text-muted"><?php echo timeAgo($resource['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo e($resource['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-secondary"><?php echo e($resource['resource_type']); ?></span>
                                            <small class="text-muted ms-2">By: <?php echo e($resource['mentor_name']); ?></small>
                                        </div>
                                        <?php if ($resource['resource_url']): ?>
                                            <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i> View
                                            </a>
                                        <?php elseif ($resource['file_path']): ?>
                                            <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Other Mentors Card -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Other Mentors (<?php echo count($otherMentors); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($otherMentors)): ?>
                            <p class="text-muted small mb-0">No other mentors assigned yet.</p>
                        <?php else: ?>
                            <?php foreach ($otherMentors as $mentor): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo getGravatar($mentor['email'], 40); ?>" class="rounded-circle me-2" alt="<?php echo e($mentor['name']); ?>">
                                <div>
                                    <div class="fw-bold"><?php echo e($mentor['name']); ?></div>
                                    <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="resources.php?action=create&project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-success">
                                <i class="fas fa-book me-1"></i> Share Resource
                            </a>
                            <a href="assessments.php?action=create&project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-info">
                                <i class="fas fa-clipboard-check me-1"></i> Create Assessment
                            </a>
                            <a href="learning.php?action=create&project_id=<?php echo $viewProject['project_id']; ?>" class="btn btn-warning">
                                <i class="fas fa-graduation-cap me-1"></i> Add Learning Objective
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Project Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Team Members:</strong>
                            <span class="float-end"><?php echo count($teamMembers); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Mentors:</strong>
                            <span class="float-end"><?php echo count($otherMentors); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Resources:</strong>
                            <span class="float-end"><?php echo count($projectResources); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Assessments:</strong>
                            <span class="float-end"><?php echo count($assessments); ?></span>
                        </div>
                        <div>
                            <strong>Learning Objectives:</strong>
                            <span class="float-end"><?php echo count($learningObjectives); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Project List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">My Projects</h1>
            <a href="available-projects.php" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Find More Projects
            </a>
        </div>

        <?php if (empty($myProjects)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                You haven't joined any projects yet. <a href="available-projects.php" class="alert-link">Browse available projects</a> to start mentoring.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($myProjects as $project): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 project-card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><?php echo e($project['project_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                                <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                            
                            <p class="card-text"><?php echo truncateText(e($project['description']), 120); ?></p>
                            
                            <div class="project-meta text-muted small mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-user me-1"></i> <?php echo e($project['project_lead_name']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-users me-1"></i> <?php echo $project['team_count']; ?> Team Members</span>
                                    <span><i class="fas fa-user-tie me-1"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                                </div>
                                <div>
                                    <i class="fas fa-calendar me-1"></i> Joined <?php echo timeAgo($project['assigned_at']); ?>
                                </div>
                            </div>

                            <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
 <?php include '../../templates/comments-section.php'; ?>
<?php include '../../templates/footer.php'; ?>