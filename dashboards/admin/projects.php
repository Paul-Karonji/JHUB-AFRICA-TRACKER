<?php
// dashboards/admin/projects.php - Project Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

// Handle view single project
$viewProject = null;
if (isset($_GET['id'])) {
    $projectId = intval($_GET['id']);
    $viewProject = $database->getRow("
        SELECT p.*, 
               COUNT(DISTINCT pi.pi_id) as innovator_count,
               COUNT(DISTINCT pm.mentor_id) as mentor_count
        FROM projects p
        LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
        LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
        WHERE p.project_id = ?
        GROUP BY p.project_id
    ", [$projectId]);

    if ($viewProject) {
        // Get team members
        $teamMembers = getProjectTeam($projectId);
        // Get mentors
        $mentors = getProjectMentors($projectId);
    }
}

// Get filter parameters
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
    $whereConditions[] = "(p.project_name LIKE ? OR p.project_lead_name LIKE ? OR p.profile_name LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all projects
$projects = $database->getRows("
    SELECT p.*, 
           COUNT(DISTINCT pi.pi_id) as innovator_count,
           COUNT(DISTINCT pm.mentor_id) as mentor_count
    FROM projects p
    LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
    LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
    {$whereClause}
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
", $params);

$pageTitle = "Project Management";
include '../../templates/header.php';
?>

<div class="projects-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Project Management</h1>
            <p class="text-muted">Manage all projects in the system</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($viewProject): ?>
    <!-- Single Project View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Project Details</h6>
            <a href="projects.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-times me-1"></i> Close
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4><?php echo e($viewProject['project_name']); ?></h4>
                            <p class="text-muted mb-2">
                                Profile: <strong><?php echo e($viewProject['profile_name']); ?></strong>
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6">Stage <?php echo $viewProject['current_stage']; ?></span><br>
                            <span class="badge bg-<?php echo $viewProject['status'] === 'active' ? 'success' : ($viewProject['status'] === 'completed' ? 'info' : 'danger'); ?> mt-2">
                                <?php echo ucfirst($viewProject['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Project Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Project Lead</th>
                                <td><?php echo e($viewProject['project_lead_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Lead Email</th>
                                <td><?php echo e($viewProject['project_lead_email']); ?></td>
                            </tr>
                            <tr>
                                <th>Project Email</th>
                                <td><?php echo e($viewProject['project_email'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Website</th>
                                <td><?php echo $viewProject['project_website'] ? '<a href="' . e($viewProject['project_website']) . '" target="_blank">' . e($viewProject['project_website']) . '</a>' : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Created Date</th>
                                <td><?php echo formatDate($viewProject['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td><?php echo nl2br(e($viewProject['description'])); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h5>Team Members (<?php echo count($teamMembers); ?>)</h5>
                        <?php if (empty($teamMembers)): ?>
                            <p class="text-muted">No team members added yet.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Experience</th>
                                        <th>Added</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td><?php echo e($member['name']); ?></td>
                                        <td><?php echo e($member['email']); ?></td>
                                        <td><?php echo e($member['role']); ?></td>
                                        <td><?php echo e($member['level_of_experience'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($member['added_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="removeTeamMember(<?php echo $member['pi_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <h5>Assigned Mentors (<?php echo count($mentors); ?>)</h5>
                        <?php if (empty($mentors)): ?>
                            <p class="text-muted">No mentors assigned yet.</p>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($mentors as $mentor): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getGravatar($mentor['email'], 50); ?>" 
                                                 class="rounded-circle me-3" alt="<?php echo e($mentor['name']); ?>">
                                            <div>
                                                <h6 class="mb-0"><?php echo e($mentor['name']); ?></h6>
                                                <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small><br>
                                                <small class="text-muted">Joined: <?php echo formatDate($mentor['assigned_at']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Project Statistics -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-bar me-2"></i>Project Statistics
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <strong><?php echo $viewProject['innovator_count']; ?></strong> Innovators
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-user-tie text-info me-2"></i>
                                    <strong><?php echo $viewProject['mentor_count']; ?></strong> Mentors
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-layer-group text-success me-2"></i>
                                    Stage <strong><?php echo $viewProject['current_stage']; ?></strong> of 6
                                </li>
                                <li>
                                    <i class="fas fa-calendar text-warning me-2"></i>
                                    <?php echo timeAgo($viewProject['created_at']); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Admin Actions -->
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning">
                            <i class="fas fa-tools me-2"></i>Admin Actions
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="../project/index.php?project_id=<?php echo $viewProject['project_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i> View as Project
                                </a>
                                <a href="../../public/project-details.php?id=<?php echo $viewProject['project_id']; ?>" 
                                   class="btn btn-outline-info btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i> Public View
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($viewProject['status'] === 'active'): ?>
                    <!-- Terminate Project -->
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">Terminate this project if it needs to be stopped.</p>
                            <button class="btn btn-danger btn-sm w-100" 
                                    onclick="terminateProject(<?php echo $viewProject['project_id']; ?>)">
                                <i class="fas fa-ban me-1"></i> Terminate Project
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Projects List -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Projects</h6>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="terminated" <?php echo $statusFilter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $stageFilter === 'all' ? 'selected' : ''; ?>>All Stages</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $stageFilter == $i ? 'selected' : ''; ?>>Stage <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by project name, lead, or profile..."
                           value="<?php echo e($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>

            <!-- Projects Table -->
            <?php if (empty($projects)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No projects found</p>
                </div>
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
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($project['project_name']); ?></strong><br>
                                <small class="text-muted"><?php echo truncateText(e($project['description']), 60); ?></small>
                            </td>
                            <td>
                                <?php echo e($project['project_lead_name']); ?><br>
                                <small class="text-muted"><?php echo e($project['profile_name']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary">Stage <?php echo $project['current_stage']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-users text-muted me-1"></i><?php echo $project['innovator_count']; ?>
                                <i class="fas fa-user-tie text-muted ms-2 me-1"></i><?php echo $project['mentor_count']; ?>
                            </td>
                            <td><?php echo formatDate($project['created_at']); ?></td>
                            <td>
                                <a href="?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i> View
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

<script>
function removeTeamMember(memberId) {
    if (!confirm('Are you sure you want to remove this team member? This action cannot be undone.')) {
        return;
    }

    fetch('../../api/projects/remove-member.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            member_id: memberId,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error: ' + error));
}

function terminateProject(projectId) {
    const reason = prompt('Please provide a reason for terminating this project:');
    if (!reason || reason.trim() === '') {
        return;
    }

    if (!confirm('Are you ABSOLUTELY SURE you want to terminate this project? This is a serious action.')) {
        return;
    }

    fetch('../../api/projects/terminate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            project_id: projectId,
            termination_reason: reason,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'projects.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error: ' + error));
}
</script>

<?php include '../../templates/footer.php'; ?>