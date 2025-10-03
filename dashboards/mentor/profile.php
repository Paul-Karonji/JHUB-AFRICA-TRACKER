<?php
// dashboards/mentor/profile.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();
$mentor = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);

$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        if ($_POST['action'] === 'update_profile') {
            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('name', 'Name is required')
                     ->required('bio', 'Bio is required')
                     ->required('area_of_expertise', 'Area of expertise is required');
            
            if (!$validator->isValid()) {
                $errors = array_merge($errors, $validator->getErrors());
            } else {
                $updateData = [
                    'name' => trim($_POST['name']),
                    'bio' => trim($_POST['bio']),
                    'area_of_expertise' => trim($_POST['area_of_expertise']),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                    'years_experience' => intval($_POST['years_experience'] ?? 0)
                ];
                
                $updated = $database->update('mentors', $updateData, 'mentor_id = ?', [$mentorId]);
                
                if ($updated) {
                    logActivity('mentor', $mentorId, 'profile_updated', 'Updated profile information');
                    $success = 'Profile updated successfully!';
                    $mentor = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);
                } else {
                    $errors[] = 'Failed to update profile';
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            // Validate password change
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errors[] = 'All password fields are required';
            } elseif (!password_verify($currentPassword, $mentor['password'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            } else {
                $updated = $database->update(
                    'mentors',
                    ['password' => Auth::hashPassword($newPassword)],
                    'mentor_id = ?',
                    [$mentorId]
                );
                
                if ($updated) {
                    logActivity('mentor', $mentorId, 'password_changed', 'Changed password');
                    $success = 'Password changed successfully!';
                } else {
                    $errors[] = 'Failed to change password';
                }
            }
        }
    }
}

// Get mentor statistics
$stats = [
    'projects' => $database->count('project_mentors', 'mentor_id = ? AND is_active = 1', [$mentorId]),
    'resources' => $database->count('mentor_resources', 'mentor_id = ? AND is_deleted = 0', [$mentorId]),
    'assessments' => $database->count('project_assessments', 'mentor_id = ? AND is_deleted = 0', [$mentorId]),
    'learning_objectives' => $database->count('learning_objectives', 'mentor_id = ? AND is_deleted = 0', [$mentorId])
];

$pageTitle = "My Profile";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <h1 class="h3 mb-4">Mentor Profile</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <?php if (is_array($error)): ?>
                        <?php foreach ($error as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><?php echo e($error); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
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
        <!-- Left Column - Profile Info -->
        <div class="col-lg-8">
            <!-- Profile Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php echo Validator::csrfInput(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo e($mentor['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-muted">(Cannot be changed)</span></label>
                            <input type="email" class="form-control" value="<?php echo e($mentor['email']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Area of Expertise <span class="text-danger">*</span></label>
                            <input type="text" name="area_of_expertise" class="form-control" 
                                   value="<?php echo e($mentor['area_of_expertise']); ?>" required
                                   placeholder="e.g., Software Development, Marketing, Finance">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio <span class="text-danger">*</span></label>
                            <textarea name="bio" class="form-control" rows="4" required><?php echo e($mentor['bio']); ?></textarea>
                            <small class="form-text text-muted">Tell projects about your experience and expertise</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo e($mentor['phone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="years_experience" class="form-control" 
                                       value="<?php echo e($mentor['years_experience'] ?? ''); ?>" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" 
                                   value="<?php echo e($mentor['linkedin_url'] ?? ''); ?>"
                                   placeholder="https://linkedin.com/in/yourprofile">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
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

        <!-- Right Column - Statistics & Info -->
        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Your Impact</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-project-diagram text-primary me-2"></i> Projects</span>
                            <strong class="h5 mb-0"><?php echo $stats['projects']; ?></strong>
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-book text-success me-2"></i> Resources</span>
                            <strong class="h5 mb-0"><?php echo $stats['resources']; ?></strong>
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clipboard-check text-info me-2"></i> Assessments</span>
                            <strong class="h5 mb-0"><?php echo $stats['assessments']; ?></strong>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-graduation-cap text-warning me-2"></i> Learning Goals</span>
                            <strong class="h5 mb-0"><?php echo $stats['learning_objectives']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Info Card -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Member Since:</small>
                        <div><?php echo formatDate($mentor['created_at'], 'F j, Y'); ?></div>
                    </div>
                    <?php if ($mentor['last_login']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Last Login:</small>
                        <div><?php echo timeAgo($mentor['last_login']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted">Account Status:</small>
                        <div>
                            <span class="badge bg-<?php echo $mentor['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $mentor['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>