<?php
// dashboards/project/profile.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        if ($_POST['action'] === 'update_info') {
            $validator = new Validator($_POST);
            $validator->required('description', 'Description is required');
            
            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
            } else {
                $updateData = [
                    'description' => trim($_POST['description']),
                    'project_email' => trim($_POST['project_email'] ?? ''),
                    'project_website' => trim($_POST['project_website'] ?? ''),
                    'target_market' => trim($_POST['target_market'] ?? ''),
                    'business_model' => trim($_POST['business_model'] ?? '')
                ];
                
                $updated = $database->update('projects', $updateData, 'project_id = ?', [$projectId]);
                
                if ($updated) {
                    logActivity('project', $projectId, 'profile_updated', 'Updated project information');
                    $success = 'Project information updated successfully!';
                    $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
                } else {
                    $errors[] = 'Failed to update information';
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errors[] = 'All password fields are required';
            } elseif (!password_verify($currentPassword, $project['password'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            } else {
                $updated = $database->update(
                    'projects',
                    ['password' => Auth::hashPassword($newPassword)],
                    'project_id = ?',
                    [$projectId]
                );
                
                if ($updated) {
                    logActivity('project', $projectId, 'password_changed', 'Changed password');
                    $success = 'Password changed successfully!';
                } else {
                    $errors[] = 'Failed to change password';
                }
            }
        }
    }
}

$pageTitle = "Project Settings";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <h1 class="h3 mb-4">Project Settings</h1>

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
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Project Information -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo Validator::csrfInput(); ?>
                        <input type="hidden" name="action" value="update_info">
                        
                        <div class="mb-3">
                            <label class="form-label">Project Name <span class="text-muted">(Cannot be changed)</span></label>
                            <input type="text" class="form-control" value="<?php echo e($project['project_name']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="5" required><?php echo e($project['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Project Email</label>
                                <input type="email" name="project_email" class="form-control" 
                                       value="<?php echo e($project['project_email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Project Website</label>
                                <input type="url" name="project_website" class="form-control" 
                                       value="<?php echo e($project['project_website'] ?? ''); ?>"
                                       placeholder="https://yourproject.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Market</label>
                            <input type="text" name="target_market" class="form-control" 
                                   value="<?php echo e($project['target_market'] ?? ''); ?>"
                                   placeholder="e.g., Small businesses in East Africa">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Business Model</label>
                            <textarea name="business_model" class="form-control" rows="3"
                                      placeholder="Describe how your project generates value..."><?php echo e($project['business_model'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Information
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo Validator::csrfInput(); ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Account Info -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-muted">Profile Name (Username):</label>
                        <div class="fw-bold"><?php echo e($project['profile_name']); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small text-muted">Project Lead:</label>
                        <div class="fw-bold"><?php echo e($project['project_lead_name']); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small text-muted">Lead Email:</label>
                        <div><?php echo e($project['project_lead_email']); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small text-muted">Member Since:</label>
                        <div><?php echo formatDate($project['created_at'], 'F j, Y'); ?></div>
                    </div>
                    
                    <div>
                        <label class="small text-muted">Current Stage:</label>
                        <div>
                            <span class="badge bg-primary"><?php echo getStageName($project['current_stage']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                        <a href="team.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-1"></i> Manage Team
                        </a>
                        <a href="progress.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-1"></i> View Progress
                        </a>
                        <a href="../../auth/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>