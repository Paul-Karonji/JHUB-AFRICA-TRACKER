<?php
// dashboards/admin/projects.php - Complete Project Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';

// Handle project actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $projectId = intval($_POST['project_id'] ?? 0);
        
        if ($action === 'terminate' && $projectId) {
            $reason = trim($_POST['termination_reason'] ?? 'No reason provided');
            
            $updated = $database->update('projects', [
                'status' => 'terminated',
                'termination_reason' => $reason
            ], 'project_id = ?', [$projectId]);
            
            if ($updated) {
                logActivity('admin', $adminId, 'project_terminated', "Terminated project ID: $projectId");
                $success = 'Project terminated successfully';
            } else {
                $errors[] = 'Failed to terminate project';
            }
        } elseif ($action === 'update_stage' && $projectId) {
            $newStage = intval($_POST['new_stage'] ?? 0);
            
            if ($newStage >= 1 && $newStage <= 6) {
                $updateData = ['current_stage' => $newStage];
                if ($newStage == 6) {
                    $updateData['status'] = 'completed';
                    $updateData['completion_date'] = date('Y-m-d H:i:s');
                }
                
                $updated = $database->update('projects', $updateData, 'project_id = ?', [$projectId]);
                
                if ($updated) {
                    logActivity('admin', $adminId, 'project_stage_updated', "Updated project stage to $newStage");
                    $success = 'Project stage updated successfully';
                } else {
                    $errors[] = 'Failed to update stage';
                }
            }
        }
    }
}

// View single project
$viewProject = null;
if (isset($_GET['id'])) {
    $projectId = intval($_GET['id']);
    $viewProject = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
    
    if ($viewProject) {
        $teamMembers = $database->getRows("
            SELECT * FROM project_innovators 
            WHERE project_id = ? AND is_active = 1
        ", [$projectId]);
        
        $mentors = $database->getRows("
            SELECT m.*, pm.assigned_at
            FROM mentors m
            INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
            WHERE pm.project_id = ? AND pm.is_active = 1
        ", [$projectId]);
        
        $projectStats = [
            'resources' => $database->count('mentor_resources', 'project_id = ? AND is_deleted = 0', [$projectId]),
            'assessments' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0', [$projectId]),
            'learning' => $database->count('learning_objectives', 'project_id = ? AND is_deleted = 0', [$projectId])
        ];
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$stageFilter = $_GET['stage'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($stageFilter !== 'all') {
    $whereConditions[] = "p.current_stage = ?";
    $params[] = intval($stageFilter);
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(p.project_name LIKE ? OR p.project_lead_name LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get projects
$projects = $database->getRows("
    SELECT p.*, 
           COUNT(DISTINCT pi.pi_id) as team_count,
           COUNT(DISTINCT pm.mentor_id) as mentor_count
    FROM projects p
    LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
    LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
    $whereClause
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
", $params);

$pageTitle = "Project Management";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo e($success); ?>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="projects.php" class="text-decoration-none text-muted mb-2 d-block">
                    <i class="fas fa-arrow-left me-1"></i> Back to All Projects
                </a>
                <h1 class="h3 mb-0"><?php echo e($viewProject['project_name']); ?></h1>
                <p class="text-muted">Project Details & Management</p>
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
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Project Information</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Project Name:</dt>
                            <dd class="col-sm-9"><?php echo e($viewProject['project_name']); ?></dd>
                            
                            <dt class="col-sm-3">Project Lead:</dt>
                            <dd class="col-sm-9"><?php echo e($viewProject['project_lead_name']); ?> (<?php echo e($viewProject['project_lead_email']); ?>)</dd>
                            
                            <dt class="col-sm-3">Profile Name:</dt>
                            <dd class="col-sm-9"><?php echo e($viewProject['profile_name']); ?></dd>
                            
                            <dt class="col-sm-3">Description:</dt>
                            <dd class="col-sm-9"><?php echo nl2br(e($viewProject['description'])); ?></dd>
                            
                            <?php if ($viewProject['project_website']): ?>
                            <dt class="col-sm-3">Website:</dt>
                            <dd class="col-sm-9"><a href="<?php echo e($viewProject['project_website']); ?>" target="_blank"><?php echo e($viewProject['project_website']); ?></a></dd>
                            <?php endif; ?>
                            
                            <?php if ($viewProject['target_market']): ?>
                            <dt class="col-sm-3">Target Market:</dt>
                            <dd class="col-sm-9"><?php echo e($viewProject['target_market']); ?></dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-3">Created:</dt>
                            <dd class="col-sm-9"><?php echo formatDate($viewProject['created_at'], 'F j, Y'); ?></dd>
                            
                            <dt class="col-sm-3">Last Updated:</dt>
                            <dd class="col-sm-9"><?php echo timeAgo($viewProject['updated_at']); ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Team Members (<?php echo count($teamMembers); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teamMembers)): ?>
                            <p class="text-muted mb-0">No team members yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamMembers as $member): ?>
                                        <tr>
                                            <td><?php echo e($member['name']); ?></td>
                                            <td><?php echo e($member['email']); ?></td>
                                            <td><?php echo e($member['role']); ?></td>
                                            <td><?php echo timeAgo($member['added_at']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mentors -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Assigned Mentors (<?php echo count($mentors); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($mentors)): ?>
                            <p class="text-muted mb-0">No mentors assigned yet.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($mentors as $mentor): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo getGravatar($mentor['email'], 50); ?>" class="rounded-circle me-3">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($mentor['name']); ?></h6>
                                            <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small>
                                            <br><small>Joined: <?php echo timeAgo($mentor['assigned_at']); ?></small>
                                        </div>
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
                <!-- Quick Stats -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Team Members:</strong>
                            <span class="float-end"><?php echo count($teamMembers); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Mentors:</strong>
                            <span class="float-end"><?php echo count($mentors); ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Resources:</strong>
                            <span class="float-end"><?php echo $projectStats['resources']; ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Assessments:</strong>
                            <span class="float-end"><?php echo $projectStats['assessments']; ?></span>
                        </div>
                        <div>
                            <strong>Learning Objectives:</strong>
                            <span class="float-end"><?php echo $projectStats['learning']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Stage Management -->
                <?php if ($viewProject['status'] === 'active'): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Stage Management</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo Validator::csrfInput(); ?>
                            <input type="hidden" name="action" value="update_stage">
                            <input type="hidden" name="project_id" value="<?php echo $viewProject['project_id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Update Stage:</label>
                                <select name="new_stage" class="form-select" required>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $viewProject['current_stage'] == $i ? 'selected' : ''; ?>>
                                            Stage <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Update Stage
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Project Actions -->
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($viewProject['status'] === 'active'): ?>
                        <p class="small text-muted">Terminate this project if it should no longer continue.</p>
                        <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#terminateModal">
                            <i class="fas fa-ban me-1"></i> Terminate Project
                        </button>
                        <?php elseif ($viewProject['status'] === 'terminated'): ?>
                        <div class="alert alert-danger mb-0">
                            <strong>Terminated</strong><br>
                            <small>Reason: <?php echo e($viewProject['termination_reason']); ?></small>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-0">Project is <?php echo $viewProject['status']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminate Modal -->
        <div class="modal fade" id="terminateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Terminate Project</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <?php echo Validator::csrfInput(); ?>
                        <input type="hidden" name="action" value="terminate">
                        <input type="hidden" name="project_id" value="<?php echo $viewProject['project_id']; ?>">
                        
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This action will terminate the project. This cannot be easily undone.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reason for Termination <span class="text-danger">*</span></label>
                                <textarea name="termination_reason" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Terminate Project</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Projects List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Project Management</h1>
                <p class="text-muted">Manage all innovation projects</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Search:</label>
                        <input type="text" name="search" class="form-control" value="<?php echo e($searchQuery); ?>" placeholder="Project name or lead...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">Status:</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="terminated" <?php echo $statusFilter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">Stage:</label>
                        <select name="stage" class="form-select">
                            <option value="all" <?php echo $stageFilter === 'all' ? 'selected' : ''; ?>>All Stages</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $stageFilter == $i ? 'selected' : ''; ?>>Stage <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <?php if ($statusFilter !== 'all' || $stageFilter !== 'all' || !empty($searchQuery)): ?>
                            <a href="projects.php" class="btn btn-sm btn-outline-secondary w-100 mt-1">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Projects (<?php echo count($projects); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <p class="text-muted text-center py-4 mb-0">No projects found matching your filters.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Lead</th>
                                    <th>Stage</th>
                                    <th>Status</th>
                                    <th>Team</th>
                                    <th>Mentors</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <a href="projects.php?id=<?php echo $project['project_id']; ?>">
                                            <?php echo e($project['project_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($project['project_lead_name']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">Stage <?php echo $project['current_stage']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $project['team_count']; ?></td>
                                    <td><?php echo $project['mentor_count']; ?></td>
                                    <td><small><?php echo formatDate($project['created_at'], 'M d, Y'); ?></small></td>
                                    <td>
                                        <a href="projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
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

<?php include '../../templates/footer.php'; ?>