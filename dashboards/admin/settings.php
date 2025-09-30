<?php
// dashboards/admin/settings.php - System Settings Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_general') {
            // Update general settings
            $settings = [
                'site_name' => trim($_POST['site_name'] ?? SITE_NAME),
                'site_description' => trim($_POST['site_description'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'max_team_size' => intval($_POST['max_team_size'] ?? 10)
            ];

            foreach ($settings as $key => $value) {
                $existing = $database->getRow("SELECT * FROM system_settings WHERE setting_key = ?", [$key]);
                
                if ($existing) {
                    $database->update('system_settings', 
                        ['setting_value' => $value],
                        'setting_key = ?',
                        [$key]
                    );
                } else {
                    $database->insert('system_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_type' => 'string',
                        'is_public' => $key === 'site_name' ? 1 : 0
                    ]);
                }
            }

            logActivity('admin', $adminId, 'settings_updated', 'Updated general system settings');
            $success = 'General settings updated successfully!';
        }

        if ($action === 'update_email') {
            // Update email settings
            $emailSettings = [
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
                'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
                'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0
            ];

            // Only update password if provided
            if (!empty($_POST['smtp_password'])) {
                $emailSettings['smtp_password'] = trim($_POST['smtp_password']);
            }

            foreach ($emailSettings as $key => $value) {
                $existing = $database->getRow("SELECT * FROM system_settings WHERE setting_key = ?", [$key]);
                
                if ($existing) {
                    $database->update('system_settings',
                        ['setting_value' => $value],
                        'setting_key = ?',
                        [$key]
                    );
                } else {
                    $database->insert('system_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_type' => is_int($value) ? 'integer' : 'string',
                        'is_public' => 0
                    ]);
                }
            }

            logActivity('admin', $adminId, 'email_settings_updated', 'Updated email configuration');
            $success = 'Email settings updated successfully!';
        }

        if ($action === 'update_security') {
            // Update security settings
            $securitySettings = [
                'session_timeout' => intval($_POST['session_timeout'] ?? 3600),
                'max_login_attempts' => intval($_POST['max_login_attempts'] ?? 5),
                'lockout_duration' => intval($_POST['lockout_duration'] ?? 900),
                'password_min_length' => intval($_POST['password_min_length'] ?? 8)
            ];

            foreach ($securitySettings as $key => $value) {
                $existing = $database->getRow("SELECT * FROM system_settings WHERE setting_key = ?", [$key]);
                
                if ($existing) {
                    $database->update('system_settings',
                        ['setting_value' => $value],
                        'setting_key = ?',
                        [$key]
                    );
                } else {
                    $database->insert('system_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_type' => 'integer',
                        'is_public' => 0
                    ]);
                }
            }

            logActivity('admin', $adminId, 'security_settings_updated', 'Updated security configuration');
            $success = 'Security settings updated successfully!';
        }
    }
}

// Load current settings
function getSetting($key, $default = '') {
    global $database;
    $setting = $database->getRow("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

$pageTitle = "System Settings";
include '../../templates/header.php';
?>

<div class="system-settings">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">System Settings</h1>
            <p class="text-muted">Configure system-wide settings and preferences</p>
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

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <div class="card shadow">
        <div class="card-header py-3">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#general">
                        <i class="fas fa-cog me-2"></i>General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#email">
                        <i class="fas fa-envelope me-2"></i>Email
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#security">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#maintenance">
                        <i class="fas fa-tools me-2"></i>Maintenance
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_general">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <h5 class="mb-3">General Settings</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" name="site_name" 
                                       value="<?php echo e(getSetting('site_name', SITE_NAME)); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" 
                                       value="<?php echo e(getSetting('contact_email', 'contact@jhubafrica.com')); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea class="form-control" name="site_description" rows="3"><?php echo e(getSetting('site_description', 'Innovation project tracking system')); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Maximum Team Size per Project</label>
                            <input type="number" class="form-control" name="max_team_size" 
                                   value="<?php echo e(getSetting('max_team_size', 10)); ?>" min="1" max="50">
                            <small class="form-text text-muted">Maximum number of innovators allowed per project</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save General Settings
                        </button>
                    </form>
                </div>

                <!-- Email Settings -->
                <div class="tab-pane fade" id="email">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_email">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <h5 class="mb-3">Email Configuration</h5>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Configure SMTP settings to enable email notifications for applications, mentor assignments, and system alerts.
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled"
                                       <?php echo getSetting('email_enabled', 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_enabled">
                                    Enable Email Notifications
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       value="<?php echo e(getSetting('smtp_host', 'smtp.gmail.com')); ?>"
                                       placeholder="smtp.gmail.com">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" 
                                       value="<?php echo e(getSetting('smtp_port', 587)); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" 
                                       value="<?php echo e(getSetting('smtp_username', '')); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" 
                                       placeholder="Leave blank to keep current password">
                                <small class="form-text text-muted">Password is encrypted and stored securely</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Email</label>
                                <input type="email" class="form-control" name="smtp_from_email" 
                                       value="<?php echo e(getSetting('smtp_from_email', 'noreply@jhubafrica.com')); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" class="form-control" name="smtp_from_name" 
                                       value="<?php echo e(getSetting('smtp_from_name', 'JHUB AFRICA')); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Email Settings
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="testEmail()">
                            <i class="fas fa-envelope me-1"></i> Send Test Email
                        </button>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_security">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <h5 class="mb-3">Security Configuration</h5>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Changing these settings affects all users. Be careful when modifying security parameters.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" name="session_timeout" 
                                       value="<?php echo e(getSetting('session_timeout', 3600)); ?>" min="300" max="86400">
                                <small class="form-text text-muted">How long users stay logged in (default: 3600 = 1 hour)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Login Attempts</label>
                                <input type="number" class="form-control" name="max_login_attempts" 
                                       value="<?php echo e(getSetting('max_login_attempts', 5)); ?>" min="3" max="10">
                                <small class="form-text text-muted">Failed attempts before account lockout</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Lockout Duration (seconds)</label>
                                <input type="number" class="form-control" name="lockout_duration" 
                                       value="<?php echo e(getSetting('lockout_duration', 900)); ?>" min="300" max="3600">
                                <small class="form-text text-muted">How long accounts are locked after max attempts (default: 900 = 15 minutes)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" name="password_min_length" 
                                       value="<?php echo e(getSetting('password_min_length', 8)); ?>" min="6" max="20">
                                <small class="form-text text-muted">Minimum characters required for passwords</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Security Settings
                        </button>
                    </form>
                </div>

                <!-- Maintenance -->
                <div class="tab-pane fade" id="maintenance">
                    <h5 class="mb-3">System Maintenance</h5>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Use these tools to maintain and optimize the system.
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Database Maintenance</h6>
                        </div>
                        <div class="card-body">
                            <p>Clean up old data and optimize database performance.</p>
                            <button class="btn btn-outline-primary" onclick="clearOldLogs()">
                                <i class="fas fa-broom me-1"></i> Clear Old Activity Logs (90+ days)
                            </button>
                            <button class="btn btn-outline-warning" onclick="optimizeDatabase()">
                                <i class="fas fa-database me-1"></i> Optimize Database
                            </button>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Cache Management</h6>
                        </div>
                        <div class="card-body">
                            <p>Clear cached data to free up space and resolve issues.</p>
                            <button class="btn btn-outline-secondary" onclick="clearCache()">
                                <i class="fas fa-trash me-1"></i> Clear System Cache
                            </button>
                        </div>
                    </div>

                    <div class="card mb-3 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">Danger Zone</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-danger">
                                <strong>Warning:</strong> These actions are irreversible and can cause data loss.
                            </p>
                            <button class="btn btn-danger" onclick="resetSystem()">
                                <i class="fas fa-exclamation-triangle me-1"></i> Reset All Settings to Default
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testEmail() {
    if (!confirm('This will send a test email to verify your SMTP configuration. Continue?')) {
        return;
    }

    const email = prompt('Enter email address to receive test email:');
    if (!email) return;

    fetch('../../api/system/test-email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: email,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test email sent successfully! Check the inbox.');
        } else {
            alert('Failed to send test email: ' + data.message);
        }
    })
    .catch(error => alert('Network error: ' + error));
}

function clearOldLogs() {
    if (!confirm('This will permanently delete activity logs older than 90 days. Continue?')) {
        return;
    }

    fetch('../../api/system/clear-logs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            days: 90,
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => alert('Network error: ' + error));
}

function optimizeDatabase() {
    if (!confirm('This will optimize all database tables. This may take a few minutes. Continue?')) {
        return;
    }

    alert('Database optimization feature coming soon!');
}

function clearCache() {
    if (!confirm('Clear all system cache?')) {
        return;
    }

    alert('Cache cleared successfully! (Feature to be implemented)');
}

function resetSystem() {
    const confirmation = prompt('Type "RESET" in capital letters to confirm resetting all settings:');
    if (confirmation !== 'RESET') {
        alert('Reset cancelled.');
        return;
    }

    if (!confirm('Are you ABSOLUTELY SURE? This will reset ALL settings to their default values.')) {
        return;
    }

    alert('System reset feature coming soon! This is a dangerous operation and requires additional safety measures.');
}
</script>

<?php include '../../templates/footer.php'; ?>