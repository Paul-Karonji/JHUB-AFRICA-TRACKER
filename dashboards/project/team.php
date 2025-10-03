<?php
// dashboards/project/team.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

$errors = [];
$success = '';

// Handle add team member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('name', 'Name is required')
                 ->required('email', 'Email is required')
                 ->email('email')
                 ->required('role', 'Role is required');
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        } else {
            // Check if email already exists
            $existing = $database->getRow("
                SELECT * FROM project_innovators 
                WHERE project_id = ? AND email = ? AND is_active = 1
            ", [$projectId, trim($_POST['email'])]);
            
            if ($existing) {
                $errors[] = 'This email is already in your team';
            } else {
                $memberData = [
                    'project_id' => $projectId,
                    'name' => trim($_POST['name']),
                    'email' => trim($_POST['email']),
                    'role' => trim($_POST['role']),
                    'level_of_experience' => trim($_POST['level_of_experience'] ?? ''),
                    'added_by_type' => 'project_lead',
                    'added_by_id' => $projectId,
                    'is_active' => 1
                ];
                
                $memberId = $database->insert('project_innovators', $memberData);
                
                if ($memberId) {
                    logActivity('project', $projectId, 'team_member_added', "Added team member: {$memberData['name']}");
                    $success = 'Team member added successfully!';
                } else {
                    $errors[] = 'Failed to add team member';
                }
            }
        }
    }
}

// Handle remove team member
if (isset($_GET['remove'])) {
    $memberId = intval($_GET['remove']);
    
    $member = $database->getRow("
        SELECT * FROM project_innovators 
        WHERE pi_id = ? AND project_id = ?
    ", [$memberId, $projectId]);
    
    if ($member) {
        $removed = $database->update('project_innovators', ['is_active' => 0], 'pi_id = ?', [$memberId]);
        
        if ($removed) {
            logActivity('project', $projectId, 'team_member_removed', "Removed team member: {$member['name']}");
            $success = 'Team member removed successfully';
        }
    }
}

// Get team members
$teamMembers = $database->getRows("
    SELECT * FROM project_innovators 
    WHERE project_id = ? AND is_active = 1
    ORDER BY added_at ASC
", [$projectId]);

$pageTitle = "Team Management";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Team Management</h1>
            <p class="text-muted">Manage your project team members</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errors as $field => $fieldErrors): ?>
                <?php if (is_array($fieldErrors)): ?>
                    <?php foreach ($fieldErrors as $error): ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div><?php echo e($fieldErrors); ?></div>
                <?php endif; ?>
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

    <div class="row">
        <!-- Add Team Member Form -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add Team Member</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo Validator::csrfInput(); ?>
                        <input type="hidden" name="action" value="add_member">
                        
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <input type="text" name="role" class="form-control" required
                                   placeholder="e.g., Developer, Designer, Marketing">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Level of Experience</label>
                            <select name="level_of_experience" class="form-select">
                                <option value="">Select level...</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                                <option value="Expert">Expert</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> Add Team Member
                        </button>
                    </form>
                </div>
            </div>

            <!-- Team Statistics -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Team Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Members:</span>
                        <strong><?php echo count($teamMembers); ?></strong>
                    </div>
                    <hr>
                    <small class="text-muted">Build a diverse team with complementary skills for project success.</small>
                </div>
            </div>
        </div>

        <!-- Team Members List -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Current Team (<?php echo count($teamMembers); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($teamMembers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Team Members Yet</h5>
                            <p class="text-muted">Add your first team member using the form on the left.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Role</th>
                                        <th>Experience</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo getGravatar($member['email'], 40); ?>" 
                                                     class="rounded-circle me-2" 
                                                     alt="<?php echo e($member['name']); ?>">
                                                <div>
                                                    <div class="fw-bold"><?php echo e($member['name']); ?></div>
                                                    <small class="text-muted"><?php echo e($member['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo e($member['role']); ?></td>
                                        <td>
                                            <?php if ($member['level_of_experience']): ?>
                                                <span class="badge bg-info"><?php echo e($member['level_of_experience']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo timeAgo($member['added_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="team.php?remove=<?php echo $member['pi_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to remove <?php echo e($member['name']); ?> from the team?')">
                                                <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>