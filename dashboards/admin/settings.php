<?php
// dashboards/admin/settings.php - Complete System Settings Management
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
            try {
                // Update general settings
                $settings = [
                    'site_name' => trim($_POST['site_name'] ?? SITE_NAME),
                    'site_description' => trim($_POST['site_description'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'max_team_size' => intval($_POST['max_team_size'] ?? 10)
                ];

                // Validate email
                if (!empty($settings['contact_email']) && !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid contact email address');
                }

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
                            'setting_type' => is_int($value) ? 'integer' : 'string',
                            'is_public' => $key === 'site_name' ? 1 : 0
                        ]);
                    }
                }

                logActivity('admin', $adminId, 'settings_updated', 'Updated general system settings');
                $success = 'General settings updated successfully!';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($action === 'update_email') {
            try {
                // Update email settings
                $emailSettings = [
                    'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                    'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                    'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                    'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
                    'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
                    'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
                    'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0
                ];

                // Validate from email
                if (!empty($emailSettings['smtp_from_email']) && !filter_var($emailSettings['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid from email address');
                }

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
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($action === 'update_security') {
            try {
                // Update security settings
                $securitySettings = [
                    'session_timeout' => intval($_POST['session_timeout'] ?? 3600),
                    'max_login_attempts' => intval($_POST['max_login_attempts'] ?? 5),
                    'lockout_duration' => intval($_POST['lockout_duration'] ?? 900),
                    'password_min_length' => intval($_POST['password_min_length'] ?? 8),
                    'require_strong_passwords' => isset($_POST['require_strong_passwords']) ? 1 : 0,
                    'enable_2fa' => isset($_POST['enable_2fa']) ? 1 : 0
                ];

                // Validate ranges
                if ($securitySettings['session_timeout'] < 300 || $securitySettings['session_timeout'] > 86400) {
                    throw new Exception('Session timeout must be between 300 and 86400 seconds');
                }
                if ($securitySettings['max_login_attempts'] < 3 || $securitySettings['max_login_attempts'] > 10) {
                    throw new Exception('Max login attempts must be between 3 and 10');
                }
                if ($securitySettings['password_min_length'] < 6 || $securitySettings['password_min_length'] > 20) {
                    throw new Exception('Password length must be between 6 and 20 characters');
                }

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
                            'setting_type' => is_int($value) ? 'integer' : 'string',
                            'is_public' => 0
                        ]);
                    }
                }

                logActivity('admin', $adminId, 'security_settings_updated', 'Updated security configuration');
                $success = 'Security settings updated successfully!';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($action === 'update_application') {
            try {
                // Update application settings
                $appSettings = [
                    'applications_enabled' => isset($_POST['applications_enabled']) ? 1 : 0,
                    'require_application_approval' => isset($_POST['require_application_approval']) ? 1 : 0,
                    'auto_notify_mentors' => isset($_POST['auto_notify_mentors']) ? 1 : 0,
                    'application_file_max_size' => intval($_POST['application_file_max_size'] ?? 10),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                    'maintenance_message' => trim($_POST['maintenance_message'] ?? 'System under maintenance')
                ];

                foreach ($appSettings as $key => $value) {
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
                            'is_public' => $key === 'maintenance_mode' ? 1 : 0
                        ]);
                    }
                }

                logActivity('admin', $adminId, 'application_settings_updated', 'Updated application settings');
                $success = 'Application settings updated successfully!';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
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
                    <a class="nav-link" data-bs-toggle="tab" href="#application">
                        <i class="fas fa-file-alt me-2"></i>Applications
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
                                       value="<?php echo e(getSetting('site_name', SITE_NAME)); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" 
                                       value="<?php echo e(getSetting('contact_email', 'contact@jhubafrica.com')); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea class="form-control" name="site_description" rows="3"><?php echo e(getSetting('site_description', 'Innovation project tracking system')); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Maximum Team Size per Project</label>
                            <input type="number" class="form-control" name="max_team_size" 
                                   value="<?php echo e(getSetting('max_team_size', 10)); ?>" min="1" max="50" required>
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       value="<?php echo e(getSetting('smtp_host', 'smtp.gmail.com')); ?>" 
                                       placeholder="smtp.gmail.com">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" 
                                       value="<?php echo e(getSetting('smtp_port', 587)); ?>" 
                                       placeholder="587">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Encryption Type</label>
                            <select class="form-select" name="smtp_encryption">
                                <option value="tls" <?php echo getSetting('smtp_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Port 587)</option>
                                <option value="ssl" <?php echo getSetting('smtp_encryption', 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo getSetting('smtp_encryption', 'tls') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" 
                                       value="<?php echo e(getSetting('smtp_username', '')); ?>" 
                                       placeholder="your-email@gmail.com">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" 
                                       placeholder="Leave blank to keep current password">
                                <small class="form-text text-muted">For Gmail, use App Password instead of regular password</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Email</label>
                                <input type="email" class="form-control" name="smtp_from_email" 
                                       value="<?php echo e(getSetting('smtp_from_email', 'noreply@jhubafrica.com')); ?>" 
                                       placeholder="noreply@jhubafrica.com">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" class="form-control" name="smtp_from_name" 
                                       value="<?php echo e(getSetting('smtp_from_name', 'JHUB AFRICA')); ?>" 
                                       placeholder="JHUB AFRICA">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Email Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="testEmail()">
                                <i class="fas fa-paper-plane me-1"></i> Send Test Email
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_security">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <h5 class="mb-3">Security Settings</h5>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Be careful when modifying security parameters. Changes take effect immediately.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" name="session_timeout" 
                                       value="<?php echo e(getSetting('session_timeout', 3600)); ?>" min="300" max="86400" required>
                                <small class="form-text text-muted">How long users stay logged in (default: 3600 = 1 hour)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Login Attempts</label>
                                <input type="number" class="form-control" name="max_login_attempts" 
                                       value="<?php echo e(getSetting('max_login_attempts', 5)); ?>" min="3" max="10" required>
                                <small class="form-text text-muted">Failed attempts before account lockout</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Lockout Duration (seconds)</label>
                                <input type="number" class="form-control" name="lockout_duration" 
                                       value="<?php echo e(getSetting('lockout_duration', 900)); ?>" min="300" max="3600" required>
                                <small class="form-text text-muted">How long accounts are locked after max attempts (default: 900 = 15 min)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" name="password_min_length" 
                                       value="<?php echo e(getSetting('password_min_length', 8)); ?>" min="6" max="20" required>
                                <small class="form-text text-muted">Minimum characters required for passwords</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="require_strong_passwords" name="require_strong_passwords"
                                       <?php echo getSetting('require_strong_passwords', 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_strong_passwords">
                                    Require Strong Passwords (uppercase, lowercase, numbers, symbols)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa"
                                       <?php echo getSetting('enable_2fa', 0) ? 'checked' : ''; ?> disabled>
                                <label class="form-check-label" for="enable_2fa">
                                    Enable Two-Factor Authentication (Coming Soon)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Security Settings
                        </button>
                    </form>
                </div>

                <!-- Application Settings -->
                <div class="tab-pane fade" id="application">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_application">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <h5 class="mb-3">Application Settings</h5>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="applications_enabled" name="applications_enabled"
                                       <?php echo getSetting('applications_enabled', 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="applications_enabled">
                                    <strong>Enable Project Applications</strong>
                                </label>
                                <small class="d-block text-muted">When disabled, new applications cannot be submitted</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="require_application_approval" name="require_application_approval"
                                       <?php echo getSetting('require_application_approval', 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_application_approval">
                                    Require Admin Approval for Applications
                                </label>
                                <small class="d-block text-muted">When disabled, applications are auto-approved</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_notify_mentors" name="auto_notify_mentors"
                                       <?php echo getSetting('auto_notify_mentors', 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_notify_mentors">
                                    Auto-notify Mentors for New Applications
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Maximum File Upload Size (MB)</label>
                            <input type="number" class="form-control" name="application_file_max_size" 
                                   value="<?php echo e(getSetting('application_file_max_size', 10)); ?>" min="1" max="50">
                            <small class="form-text text-muted">Maximum size for pitch decks and documents</small>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Maintenance Mode</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            When enabled, only admins can access the system. All other users will see a maintenance page.
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode"
                                       <?php echo getSetting('maintenance_mode', 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    <strong>Enable Maintenance Mode</strong>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Maintenance Message</label>
                            <textarea class="form-control" name="maintenance_message" rows="2"><?php echo e(getSetting('maintenance_message', 'System is currently under maintenance. Please check back later.')); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Application Settings
                        </button>
                    </form>
                </div>

                <!-- Maintenance Tab -->
                <div class="tab-pane fade" id="maintenance">
                    <h5 class="mb-3">System Maintenance</h5>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Use these tools to maintain and optimize the system.
                    </div>

                    <!-- Database Maintenance -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-database me-2"></i>Database Maintenance</h6>
                        </div>
                        <div class="card-body">
                            <p>Clean up old data and optimize database performance.</p>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary" onclick="clearOldLogs()">
                                    <i class="fas fa-broom me-1"></i> Clear Old Activity Logs (90+ days)
                                </button>
                                <button class="btn btn-outline-warning" onclick="optimizeDatabase()">
                                    <i class="fas fa-database me-1"></i> Optimize Database Tables
                                </button>
                                <button class="btn btn-outline-info" onclick="createBackup()">
                                    <i class="fas fa-download me-1"></i> Create Database Backup
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Management -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-memory me-2"></i>Cache Management</h6>
                        </div>
                        <div class="card-body">
                            <p>Clear cached data to free up space and resolve issues.</p>
                            <button class="btn btn-outline-secondary" onclick="clearCache()">
                                <i class="fas fa-trash me-1"></i> Clear System Cache
                            </button>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Database:</strong> <?php echo $database->getRow("SELECT VERSION() as version")['version']; ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>System Version:</strong> <?php echo SITE_VERSION; ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="card mb-3 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h6>
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
// Test Email Function
function testEmail() {
    if (!confirm('This will send a test email to verify your SMTP configuration. Continue?')) {
        return;
    }

    const email = prompt('Enter email address to receive test email:');
    if (!email) return;

    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

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
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ Test email sent successfully! Check the inbox for: ' + email);
        } else {
            alert('❌ Failed to send test email: ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}

// Clear Old Logs Function
function clearOldLogs() {
    if (!confirm('This will permanently delete activity logs older than 90 days. Continue?')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Clearing...';

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
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        alert(data.message);
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}

// Optimize Database Function
function optimizeDatabase() {
    if (!confirm('This will optimize all database tables. This may take a few minutes. Continue?')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Optimizing...';

    fetch('../../api/system/optimize-database.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ ' + data.message + '\n\nTables optimized: ' + data.tables_optimized);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}

// Create Backup Function
function createBackup() {
    if (!confirm('This will create a backup of the entire database. Continue?')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creating Backup...';

    fetch('../../api/system/create-backup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ ' + data.message);
            if (data.download_url) {
                window.location.href = data.download_url;
            }
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}

// Clear Cache Function
function clearCache() {
    if (!confirm('Clear all system cache?')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Clearing...';

    fetch('../../api/system/clear-cache.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ Cache cleared successfully!');
            window.location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}

// Reset System Function
function resetSystem() {
    const confirmation = prompt('Type "RESET" in capital letters to confirm resetting all settings:');
    if (confirmation !== 'RESET') {
        alert('Reset cancelled.');
        return;
    }

    if (!confirm('Are you ABSOLUTELY SURE? This will reset ALL settings to their default values.')) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Resetting...';

    fetch('../../api/system/reset-settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ ' + data.message);
            window.location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Network error: ' + error);
    });
}
</script>

<?php include '../../templates/footer.php'; ?>