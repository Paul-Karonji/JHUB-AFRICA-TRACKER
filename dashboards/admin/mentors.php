<?php
// dashboards/admin/mentors.php - Mentor Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

// Handle view single mentor
$viewMentor = null;
if (isset($_GET['id'])) {
    $mentorId = intval($_GET['id']);
    $viewMentor = $database->getRow("
        SELECT m.*,
               COUNT(DISTINCT pm.project_id) as project_count
        FROM mentors m
        LEFT JOIN project_mentors pm ON m.mentor_id = pm.mentor_id AND pm.is_active = 1
        WHERE m.mentor_id = ?
        GROUP BY m.mentor_id
    ", [$mentorId]);

    if ($viewMentor) {
        // Get assigned projects
        $assignedProjects = $database->getRows("
            SELECT p.*, pm.assigned_at, pm.notes
            FROM project_mentors pm
            INNER JOIN projects p ON pm.project_id = p.project_id
            WHERE pm.mentor_id = ? AND pm.is_active = 1
            ORDER BY pm.assigned_at DESC
        ", [$mentorId]);
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter === 'active') {
    $whereConditions[] = "m.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "m.is_active = 0";
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(m.name LIKE ? OR m.email LIKE ? OR m.area_of_expertise LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all mentors
$mentors = $database->getRows("
    SELECT m.*,
           COUNT(DISTINCT pm.project_id) as project_count,
           a.username as created_by_username
    FROM mentors m
    LEFT JOIN project_mentors pm ON m.mentor_id = pm.mentor_id AND pm.is_active = 1
    LEFT JOIN admins a ON m.created_by = a.admin_id
    {$whereClause}
    GROUP BY m.mentor_id
    ORDER BY m.created_at DESC
", $params);

// Get statistics
$mentorStats = [
    'total' => $database->count('mentors'),
    'active' => $database->count('mentors', 'is_active = 1'),
    'inactive' => $database->count('mentors', 'is_active = 0')
];

$pageTitle = "Mentor Management";
include '../../templates/header.php';
?>

<div class="mentors-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Mentor Management</h1>
            <p class="text-muted">Manage mentors and their assignments</p>
        </div>
        <div>
            <a href="register-mentor.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Register New Mentor
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Mentors</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $mentorStats['total']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Mentors</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $mentorStats['active']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Inactive Mentors</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $mentorStats['inactive']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($viewMentor): ?>
    <!-- Single Mentor View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Mentor Details</h6>
            <a href="mentors.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-times me-1"></i> Close
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex align-items-start mb-4">
                        <img src="<?php echo getGravatar($viewMentor['email'], 100); ?>" 
                             class="rounded-circle me-3" alt="<?php echo e($viewMentor['name']); ?>">
                        <div class="flex-grow-1">
                            <h4 class="mb-1"><?php echo e($viewMentor['name']); ?></h4>
                            <p class="text-muted mb-2"><?php echo e($viewMentor['area_of_expertise']); ?></p>
                            <p class="mb-1">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <a href="mailto:<?php echo e($viewMentor['email']); ?>"><?php echo e($viewMentor['email']); ?></a>
                            </p>
                            <?php if ($viewMentor['phone']): ?>
                            <p class="mb-1">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <?php echo e($viewMentor['phone']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($viewMentor['linkedin_url']): ?>
                            <p class="mb-1">
                                <i class="fab fa-linkedin text-muted me-2"></i>
                                <a href="<?php echo e($viewMentor['linkedin_url']); ?>" target="_blank">LinkedIn Profile</a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Biography</h5>
                        <p><?php echo nl2br(e($viewMentor['bio'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h5>Account Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Status</th>
                                <td>
                                    <span class="badge bg-<?php echo $viewMentor['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $viewMentor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Years of Experience</th>
                                <td><?php echo $viewMentor['years_experience'] ? e($viewMentor['years_experience']) . ' years' : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Registered Date</th>
                                <td><?php echo formatDate($viewMentor['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Last Login</th>
                                <td><?php echo $viewMentor['last_login'] ? formatDate($viewMentor['last_login']) : 'Never'; ?></td>
                            </tr>
                            <tr>
                                <th>Assigned Projects</th>
                                <td><strong><?php echo $viewMentor['project_count']; ?></strong> active projects</td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h5>Assigned Projects (<?php echo count($assignedProjects); ?>)</h5>
                        <?php if (empty($assignedProjects)): ?>
                            <p class="text-muted">This mentor is not assigned to any projects yet.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Stage</th>
                                        <th>Status</th>
                                        <th>Assigned Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedProjects as $project): ?>
                                    <tr>
                                        <td><?php echo e($project['project_name']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo getStageName($project['current_stage']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($project['assigned_at']); ?></td>
                                        <td>
                                            <a href="projects.php?id=<?php echo $project['project_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Project
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

                <div class="col-md-4">
                    <!-- Mentor Actions -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-tools me-2"></i>Mentor Actions
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($viewMentor['is_active']): ?>
                                <button class="btn btn-warning btn-sm" 
                                        onclick="toggleMentorStatus(<?php echo $viewMentor['mentor_id']; ?>, 0)">
                                    <i class="fas fa-ban me-1"></i> Deactivate Account
                                </button>
                                <?php else: ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="toggleMentorStatus(<?php echo $viewMentor['mentor_id']; ?>, 1)">
                                    <i class="fas fa-check me-1"></i> Activate Account
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-info btn-sm" 
                                        onclick="resetMentorPassword(<?php echo $viewMentor['mentor_id']; ?>)">
                                    <i class="fas fa-key me-1"></i> Reset Password
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-chart-bar me-2"></i>Mentor Statistics
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-project-diagram text-primary me-2"></i>
                                    <strong><?php echo $viewMentor['project_count']; ?></strong> Active Projects
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar text-success me-2"></i>
                                    Joined <?php echo timeAgo($viewMentor['created_at']); ?>
                                </li>
                                <li>
                                    <i class="fas fa-clock text-info me-2"></i>
                                    Last login: <?php echo $viewMentor['last_login'] ? timeAgo($viewMentor['last_login']) : 'Never'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Mentors List -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Mentors</h6>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name, email, or expertise..."
                           value="<?php echo e($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>

            <!-- Mentors Grid -->
            <?php if (empty($mentors)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No mentors found</p>
                    <a href="register-mentor.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Register First Mentor
                    </a>
                </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($mentors as $mentor): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 <?php echo $mentor['is_active'] ? 'border-success' : 'border-secondary'; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <img src="<?php echo getGravatar($mentor['email'], 60); ?>" 
                                     class="rounded-circle me-3" alt="<?php echo e($mentor['name']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo e($mentor['name']); ?></h6>
                                    <p class="text-muted small mb-1"><?php echo e($mentor['area_of_expertise']); ?></p>
                                    <span class="badge bg-<?php echo $mentor['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $mentor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>

                            <p class="card-text small"><?php echo truncateText(e($mentor['bio']), 100); ?></p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-project-diagram me-1"></i>
                                    <?php echo $mentor['project_count']; ?> projects
                                </small>
                                <a href="?id=<?php echo $mentor['mentor_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleMentorStatus(mentorId, newStatus) {
    const action = newStatus === 1 ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this mentor account?`)) {
        return;
    }

    fetch('../../api/mentors/toggle-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mentor_id: mentorId,
            is_active: newStatus,
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

function resetMentorPassword(mentorId) {
    const newPassword = prompt('Enter new password for this mentor (min 8 characters):');
    if (!newPassword || newPassword.length < 8) {
        alert('Password must be at least 8 characters long');
        return;
    }

    if (!confirm('Are you sure you want to reset this mentor\'s password?')) {
        return;
    }

    fetch('../../api/mentors/reset-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mentor_id: mentorId,
            new_password: newPassword,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message + '\n\nNew password: ' + newPassword + '\n\nPlease save this password and share it securely with the mentor.');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error: ' + error));
}
</script>

<?php include '../../templates/footer.php'; ?>