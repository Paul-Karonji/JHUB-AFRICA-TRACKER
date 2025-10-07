<?php
// dashboards/admin/admin-management.php - Admin Account Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$currentAdminId = $auth->getUserId();
$errors = [];
$success = '';

// Handle add new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('username', 'Username is required')
                 ->required('password', 'Password is required')
                 ->min('password', 8);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        } else {
            // Check if username exists
            $existingAdmin = $database->getRow(
                "SELECT username FROM admins WHERE username = ?",
                [$_POST['username']]
            );

            if ($existingAdmin) {
                $errors['username'][] = 'Username already exists';
            } else {
                $adminData = [
                    'username' => trim($_POST['username']),
                    'password' => Auth::hashPassword($_POST['password']),
                    'admin_name' => !empty($_POST['admin_name']) ? trim($_POST['admin_name']) : null,
                    'created_by' => $currentAdminId,
                    'is_active' => 1
                ];

                $newAdminId = $database->insert('admins', $adminData);

                if ($newAdminId) {
                    logActivity(
                        'admin',
                        $currentAdminId,
                        'admin_created',
                        "Created new admin account: {$adminData['username']}",
                        null,
                        ['new_admin_id' => $newAdminId]
                    );

                    $success = "Admin account created successfully!";
                    $_POST = [];
                } else {
                    $errors[] = 'Failed to create admin account';
                }
            }
        }
    }
}

// Get all admins
$admins = $database->getRows("
    SELECT a.*, 
           ca.username as creator_username,
           COUNT(DISTINCT pa.application_id) as applications_reviewed
    FROM admins a
    LEFT JOIN admins ca ON a.created_by = ca.admin_id
    LEFT JOIN project_applications pa ON a.admin_id = pa.reviewed_by
    GROUP BY a.admin_id
    ORDER BY a.created_at DESC
");

$pageTitle = "Admin Management";
include '../../templates/header.php';
?>

<div class="admin-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Admin Management</h1>
            <p class="text-muted">Manage admin accounts and permissions</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo e($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors) && is_array($errors) && !isset($errors['username'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add New Admin Form -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-plus me-2"></i>Add New Admin
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?php echo e($errors['username'][0]); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="admin_name" class="form-label">Admin Name/Description</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" 
                                   value="<?php echo e($_POST['admin_name'] ?? ''); ?>" 
                                   placeholder="e.g., John Doe, HR Admin">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" minlength="8" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?php echo e($errors['password'][0]); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-secondary w-100" onclick="generatePassword()">
                                <i class="fas fa-random me-1"></i> Generate Strong Password
                            </button>
                        </div>

                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> Save the password securely. It cannot be retrieved later.
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Create Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admins List -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users-cog me-2"></i>All Admin Accounts (<?php echo count($admins); ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($admins)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users-cog fa-4x text-muted mb-3"></i>
                            <p class="text-muted">No admin accounts found</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Admin Name</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr class="<?php echo $admin['admin_id'] === $currentAdminId ? 'table-primary' : ''; ?>">
                                    <td>
                                        <strong><?php echo e($admin['username']); ?></strong>
                                        <?php if ($admin['admin_id'] === $currentAdminId): ?>
                                            <span class="badge bg-info ms-2">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($admin['admin_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()): ?>
                                            <span class="badge bg-warning text-dark ms-1">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatDate($admin['created_at']); ?><br>
                                        <small class="text-muted">
                                            by <?php echo e($admin['creator_username'] ?? 'System'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($admin['last_login']): ?>
                                            <?php echo timeAgo($admin['last_login']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($admin['admin_id'] !== $currentAdminId): ?>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($admin['is_active']): ?>
                                            <button class="btn btn-warning" 
                                                    onclick="toggleAdminStatus(<?php echo $admin['admin_id']; ?>, 0)"
                                                    title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-success" 
                                                    onclick="toggleAdminStatus(<?php echo $admin['admin_id']; ?>, 1)"
                                                    title="Activate">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-info" 
                                                    onclick="resetAdminPassword(<?php echo $admin['admin_id']; ?>)"
                                                    title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                onclick="changeOwnPassword()"
                                                title="Change Your Password">
                                            <i class="fas fa-key me-1"></i> Change Password
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Admin Statistics -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Admin Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Applications Reviewed</th>
                                    <th>Account Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo e($admin['username']); ?></td>
                                    <td><?php echo $admin['applications_reviewed']; ?> applications</td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.getElementById('password').value = password;
    document.getElementById('password').type = 'text';
    alert('Generated password: ' + password + '\n\nPlease save this password securely!');
}

function toggleAdminStatus(adminId, newStatus) {
    const action = newStatus === 1 ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this admin account?`)) {
        return;
    }

    fetch('../../api/admins/toggle-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            admin_id: adminId,
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

function resetAdminPassword(adminId) {
    const newPassword = prompt('Enter new password for this admin (min 8 characters):');
    if (!newPassword || newPassword.length < 8) {
        alert('Password must be at least 8 characters long');
        return;
    }

    if (!confirm('Are you sure you want to reset this admin password?')) {
        return;
    }

    fetch('../../api/admins/reset-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            admin_id: adminId,
            new_password: newPassword,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message + '\n\nNew password: ' + newPassword + '\n\nPlease save this securely!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error: ' + error));
}

function changeOwnPassword() {
    window.location.href = 'change-password.php';
}
</script>

<?php include '../../templates/footer.php'; ?>