<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Admin-Specific Login Page
 * 
 * Direct login page for administrators with enhanced security features.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/../includes/init.php';

// Initialize authentication
$auth = new Auth();

// Check if user is already logged in
if ($auth->isAuthenticated()) {
    $userType = $auth->getUserType();
    
    // If already logged in as admin, redirect to dashboard
    if ($userType === 'admin') {
        redirect(AppConfig::getDashboardUrl('admin'));
    } else {
        // Logged in as different user type, show message
        $loginError = 'You are currently logged in as ' . ucfirst($userType) . '. Please logout first to access admin login.';
    }
}

// Handle login form submission
$loginResult = null;
$loginError = $loginError ?? null;

if (isPostRequest()) {
    try {
        // Verify CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$auth->verifyCSRFToken($csrfToken)) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $loginResult = $auth->loginAdmin($username, $password);
        
        // Handle successful login
        if ($loginResult && $loginResult['success']) {
            redirect($loginResult['redirect']);
        } else {
            $loginError = $loginResult['message'] ?? 'Login failed';
        }
        
    } catch (Exception $e) {
        $loginError = $e->getMessage();
        logActivity('ERROR', "Admin login form error: " . $e->getMessage());
    }
}

$pageTitle = 'Admin Login - JHUB AFRICA';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8faff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .admin-login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(21, 101, 192, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(21, 101, 192, 0.1);
        }

        .admin-header {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="70" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="80" r="2.5" fill="rgba(255,255,255,0.1)"/></svg>');
        }

        .admin-logo {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.8rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 1.5rem auto;
            position: relative;
            z-index: 1;
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .admin-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .security-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            z-index: 2;
        }

        .admin-content {
            padding: 2.5rem 2rem;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #f44336;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .blocked-message {
            background: #fff3e0;
            color: #e65100;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ff9800;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #0d47a1;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e3f2fd;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafbff;
        }

        .form-input:focus {
            outline: none;
            border-color: #1565c0;
            background: white;
            box-shadow: 0 0 0 4px rgba(21, 101, 192, 0.1);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64b5f6;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            background: rgba(21, 101, 192, 0.1);
        }

        .admin-login-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .admin-login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .admin-login-btn:hover::before {
            left: 100%;
        }

        .admin-login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(13, 71, 161, 0.4);
        }

        .admin-login-btn:active {
            transform: translateY(-1px);
        }

        .admin-login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .security-info {
            background: #e8f5e8;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            border-left: 4px solid #4caf50;
            font-size: 0.85rem;
            color: #2e7d32;
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e3f2fd;
        }

        .back-link {
            color: #64b5f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            color: #1565c0;
        }

        .system-info {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #90a4ae;
            text-align: center;
        }

        @media (max-width: 768px) {
            .admin-login-container {
                max-width: 100%;
                margin: 0;
                border-radius: 0;
            }
            
            .admin-header {
                padding: 2rem 1.5rem;
            }
            
            .admin-content {
                padding: 2rem 1.5rem;
            }
            
            .admin-title {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <div class="security-badge">🔒 Secure</div>
            <div class="admin-logo">🔧</div>
            <h1 class="admin-title">Administrator</h1>
            <p class="admin-subtitle">System Administration Portal</p>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
            <!-- Display Error Messages -->
            <?php if ($loginError): ?>
                <?php if (isset($loginResult['blocked_until'])): ?>
                    <div class="blocked-message">
                        <strong>Account Temporarily Locked</strong><br>
                        Too many failed login attempts. Please try again after 
                        <?php echo date('H:i:s', $loginResult['blocked_until']); ?>.
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        <span>⚠️</span>
                        <?php echo htmlspecialchars($loginError); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Security Information -->
            <div class="security-info">
                <strong>🛡️ Admin Security Notice:</strong><br>
                This is a restricted area for system administrators only. All login attempts are logged and monitored.
            </div>

            <!-- Admin Login Form -->
            <form method="POST" action="" id="adminLoginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label">Administrator Username</label>
                    <input type="text" name="username" class="form-input" required 
                           placeholder="Enter your admin username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Administrator Password</label>
                    <div class="password-field">
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Enter your secure password" id="adminPassword"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()" title="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="admin-login-btn" id="loginButton">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    <span class="button-text">Access Admin Dashboard</span>
                </button>
            </form>

            <!-- Form Footer -->
            <div class="form-footer">
                <a href="<?php echo AppConfig::getUrl('auth/login.php'); ?>" class="back-link">
                    ← Other Login Types
                </a>
                
                <div class="system-info">
                    JHUB AFRICA v<?php echo AppConfig::APP_VERSION; ?> | 
                    Secure Administrator Access
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePassword() {
            const input = document.getElementById('adminPassword');
            const button = document.querySelector('.password-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🙈';
                button.title = 'Hide password';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
                button.title = 'Show password';
            }
        }

        // Enhanced form handling
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const spinner = document.getElementById('loadingSpinner');
            const text = button.querySelector('.button-text');
            
            // Show loading state
            button.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'Authenticating...';
            
            // Validate form
            const username = document.querySelector('input[name="username"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                button.disabled = false;
                spinner.style.display = 'none';
                text.textContent = 'Access Admin Dashboard';
                
                // Show error
                showError('Please enter both username and password');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                button.disabled = false;
                spinner.style.display = 'none';
                text.textContent = 'Access Admin Dashboard';
                
                showError('Username must be at least 3 characters long');
                return;
            }
        });

        // Enhanced input validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', function() {
                // Remove error styling when user types
                this.style.borderColor = '';
                hideError();
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#1565c0';
            });

            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.style.borderColor = '#f44336';
                } else if (this.value.trim()) {
                    this.style.borderColor = '#4caf50';
                }
            });
        });

        // Error display functions
        function showError(message) {
            hideError(); // Remove existing error first
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<span>⚠️</span>${message}`;
            
            const form = document.getElementById('adminLoginForm');
            form.insertBefore(errorDiv, form.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(hideError, 5000);
        }

        function hideError() {
            const existingError = document.querySelector('.error-message:not([data-server-error])');
            if (existingError) {
                existingError.remove();
            }
        }

        // Mark server errors so they don't get auto-removed
        document.addEventListener('DOMContentLoaded', function() {
            const serverError = document.querySelector('.error-message');
            if (serverError) {
                serverError.setAttribute('data-server-error', 'true');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + A focuses username field
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                document.querySelector('input[name="username"]').focus();
            }
            
            // Escape clears form
            if (e.key === 'Escape') {
                document.getElementById('adminLoginForm').reset();
                document.querySelector('input[name="username"]').focus();
            }
        });

        // Security: Warn about caps lock
        document.querySelector('input[name="password"]').addEventListener('keydown', function(e) {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                showTemporaryMessage('⚠️ Caps Lock is ON', 'warning');
            }
        });

        function showTemporaryMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'warning' ? '#fff3e0' : '#e3f2fd'};
                color: ${type === 'warning' ? '#e65100' : '#1565c0'};
                padding: 0.8rem 1.2rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 0.9rem;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            messageDiv.textContent = message;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }

        // Add CSS animations for temporary messages
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        <?php if (DEVELOPMENT_MODE): ?>
        // Development mode helpers
        console.log('Admin Login - Development Mode');
        
        // Quick fill for testing (remove in production)
        function quickFillAdmin() {
            document.querySelector('input[name="username"]').value = 'admin';
            document.querySelector('input[name="password"]').value = 'JhubAfrica2024!';
            showTemporaryMessage('Development: Admin credentials filled', 'info');
        }
        
        // Add development helper
        const devButton = document.createElement('button');
        devButton.textContent = 'Dev: Fill Admin';
        devButton.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ff9800;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            z-index: 9999;
        `;
        devButton.onclick = quickFillAdmin;
        document.body.appendChild(devButton);
        <?php endif; ?>
    </script>
</body>
</html>