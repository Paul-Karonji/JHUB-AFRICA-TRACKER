<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Universal Login Page
 * 
 * This page provides a unified login interface for all user types
 * with tabs for Admin, Mentor, and Project login.
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
    redirect(AppConfig::getDashboardUrl($userType));
}

// Handle login form submissions
$loginResult = null;
$loginError = null;

if (isPostRequest()) {
    try {
        // Verify CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$auth->verifyCSRFToken($csrfToken)) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }
        
        $loginType = $_POST['login_type'] ?? '';
        
        switch ($loginType) {
            case 'admin':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $loginResult = $auth->loginAdmin($username, $password);
                break;
                
            case 'mentor':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $loginResult = $auth->loginMentor($email, $password);
                break;
                
            case 'project':
                $profileName = trim($_POST['profile_name'] ?? '');
                $password = $_POST['password'] ?? '';
                $loginResult = $auth->loginProject($profileName, $password);
                break;
                
            default:
                throw new Exception('Invalid login type');
        }
        
        // Handle successful login
        if ($loginResult && $loginResult['success']) {
            // Redirect to appropriate dashboard
            redirect($loginResult['redirect']);
        } else {
            $loginError = $loginResult['message'] ?? 'Login failed';
        }
        
    } catch (Exception $e) {
        $loginError = $e->getMessage();
        logActivity('ERROR', "Login form error: " . $e->getMessage());
    }
}

// Page configuration
$pageTitle = 'Login - JHUB AFRICA';
$pageDescription = 'Login to your JHUB AFRICA account to access your dashboard and manage your innovation projects.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/auth.css'); ?>">
    
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

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(21, 101, 192, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .logo-section {
            margin-bottom: 1rem;
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 1rem auto;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .login-content {
            padding: 2rem;
        }

        .login-tabs {
            display: flex;
            background: #f8faff;
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 2rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.8rem 1rem;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            color: #64b5f6;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .tab-btn.active {
            background: white;
            color: #1565c0;
            box-shadow: 0 2px 8px rgba(21, 101, 192, 0.1);
        }

        .tab-btn:hover:not(.active) {
            background: rgba(66, 165, 245, 0.1);
        }

        .login-form {
            display: none;
        }

        .login-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1565c0;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafbff;
        }

        .form-input:focus {
            outline: none;
            border-color: #42a5f5;
            background: white;
            box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.1);
        }

        .form-input:invalid {
            border-color: #f44336;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 165, 245, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
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

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        }

        .back-link:hover {
            color: #1565c0;
        }

        .form-help {
            font-size: 0.8rem;
            color: #90a4ae;
            margin-top: 0.3rem;
        }

        .blocked-message {
            background: #fff3e0;
            color: #e65100;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #ff9800;
            font-size: 0.9rem;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64b5f6;
            cursor: pointer;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .login-container {
                max-width: 100%;
                margin: 0;
                border-radius: 0;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-content {
                padding: 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }

        /* Security indicator */
        .security-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #4caf50;
            margin-top: 1rem;
        }

        .security-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #4caf50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-section">
                <div class="logo-placeholder">JHUB</div>
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Login to access your innovation dashboard</p>
        </div>

        <!-- Login Content -->
        <div class="login-content">
            <!-- Display Error Messages -->
            <?php if ($loginError): ?>
                <div class="error-message">
                    <span>⚠️</span>
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>

            <!-- Login Tabs -->
            <div class="login-tabs">
                <button class="tab-btn active" onclick="switchTab('admin')">🔧 Admin</button>
                <button class="tab-btn" onclick="switchTab('mentor')">🏆 Mentor</button>
                <button class="tab-btn" onclick="switchTab('project')">📋 Project</button>
            </div>

            <!-- Admin Login Form -->
            <form class="login-form active" id="adminForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" name="login_type" value="admin">
                
                <div class="form-group">
                    <label class="form-label">Admin Username</label>
                    <input type="text" name="username" class="form-input" required 
                           placeholder="Enter your admin username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-toggle">
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Enter your password" id="adminPassword"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('adminPassword')">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-button">
                    <span class="loading-spinner"></span>
                    <span class="button-text">Login as Admin</span>
                </button>
                
                <div class="security-indicator">
                    <div class="security-icon">🔒</div>
                    <span>Secure admin access with full system privileges</span>
                </div>
            </form>

            <!-- Mentor Login Form -->
            <form class="login-form" id="mentorForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" name="login_type" value="mentor">
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required 
                           placeholder="Enter your registered email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-toggle">
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Enter your password" id="mentorPassword"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('mentorPassword')">
                            👁️
                        </button>
                    </div>
                    <div class="form-help">Use the password provided by the admin during registration</div>
                </div>
                
                <button type="submit" class="login-button">
                    <span class="loading-spinner"></span>
                    <span class="button-text">Login as Mentor</span>
                </button>
                
                <div class="security-indicator">
                    <div class="security-icon">🏆</div>
                    <span>Access mentor dashboard to guide innovation projects</span>
                </div>
            </form>

            <!-- Project Login Form -->
            <form class="login-form" id="projectForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" name="login_type" value="project">
                
                <div class="form-group">
                    <label class="form-label">Profile Name</label>
                    <input type="text" name="profile_name" class="form-input" required 
                           placeholder="Enter your project profile name"
                           value="<?php echo htmlspecialchars($_POST['profile_name'] ?? ''); ?>"
                           autocomplete="username">
                    <div class="form-help">This is the unique username created when your project was set up</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Project Password</label>
                    <div class="password-toggle">
                        <input type="password" name="password" class="form-input" required 
                               placeholder="Enter your project password" id="projectPassword"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('projectPassword')">
                            👁️
                        </button>
                    </div>
                    <div class="form-help">All team members use the same project credentials</div>
                </div>
                
                <button type="submit" class="login-button">
                    <span class="loading-spinner"></span>
                    <span class="button-text">Access Project</span>
                </button>
                
                <div class="security-indicator">
                    <div class="security-icon">📋</div>
                    <span>Manage your project team, progress, and discussions</span>
                </div>
            </form>

            <!-- Footer -->
            <div class="form-footer">
                <a href="<?php echo AppConfig::getUrl(); ?>" class="back-link">
                    ← Back to Home
                </a>
                
                <div style="margin-top: 1rem; font-size: 0.8rem; color: #90a4ae;">
                    Don't have a project yet? 
                    <a href="<?php echo AppConfig::getUrl('public/create-project.php'); ?>" style="color: #42a5f5;">
                        Create one now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabType) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update forms
            document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));
            document.getElementById(tabType + 'Form').classList.add('active');
            
            // Focus first input
            setTimeout(() => {
                const activeForm = document.getElementById(tabType + 'Form');
                const firstInput = activeForm.querySelector('.form-input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        // Password visibility toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🙈';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
            }
        }

        // Form submission handling
        document.querySelectorAll('.login-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('.login-button');
                const spinner = button.querySelector('.loading-spinner');
                const text = button.querySelector('.button-text');
                
                // Show loading state
                button.disabled = true;
                spinner.style.display = 'inline-block';
                text.textContent = 'Logging in...';
                
                // Form will submit normally, this just provides visual feedback
            });
        });

        // Auto-focus first input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeForm = document.querySelector('.login-form.active');
            const firstInput = activeForm.querySelector('.form-input');
            if (firstInput) firstInput.focus();
            
            // Check for URL parameters to switch tabs
            const urlParams = new URLSearchParams(window.location.search);
            const userType = urlParams.get('type');
            if (userType && ['admin', 'mentor', 'project'].includes(userType)) {
                // Find the corresponding tab button and click it
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    if (btn.textContent.toLowerCase().includes(userType)) {
                        btn.click();
                    }
                });
            }
        });

        // Enhanced form validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', function() {
                // Remove invalid styling when user starts typing
                this.classList.remove('invalid');
                
                // Real-time validation feedback
                if (this.type === 'email' && this.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.value)) {
                        this.style.borderColor = '#f44336';
                    } else {
                        this.style.borderColor = '#4caf50';
                    }
                }
            });
            
            input.addEventListener('blur', function() {
                // Validate on blur
                if (this.hasAttribute('required') && !this.value) {
                    this.style.borderColor = '#f44336';
                } else if (this.checkValidity()) {
                    this.style.borderColor = '#4caf50';
                }
            });
        });

        // Handle browser back button
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.tab) {
                switchTabProgrammatically(e.state.tab);
            }
        });

        // Add state to history when switching tabs
        function switchTabProgrammatically(tabType) {
            document.querySelectorAll('.tab-btn').forEach((btn, index) => {
                btn.classList.remove('active');
                if (['admin', 'mentor', 'project'][index] === tabType) {
                    btn.classList.add('active');
                }
            });
            
            document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));
            document.getElementById(tabType + 'Form').classList.add('active');
        }

        // Prevent form resubmission on page reload
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        <?php if (DEVELOPMENT_MODE): ?>
        // Development helpers
        console.log('JHUB AFRICA Login Page - Development Mode');
        console.log('Available login types: Admin, Mentor, Project');
        
        // Quick fill for development (remove in production)
        function quickFillAdmin() {
            document.querySelector('#adminForm input[name="username"]').value = 'admin';
            document.querySelector('#adminForm input[name="password"]').value = 'JhubAfrica2024!';
        }
        
        // Add quick fill button in development
        if (<?php echo DEVELOPMENT_MODE ? 'true' : 'false'; ?>) {
            const devHelper = document.createElement('div');
            devHelper.innerHTML = '<button onclick="quickFillAdmin()" style="position:fixed;bottom:10px;right:10px;background:#ff9800;color:white;border:none;padding:5px 10px;border-radius:5px;font-size:12px;cursor:pointer;">Quick Fill Admin</button>';
            document.body.appendChild(devHelper);
        }
        <?php endif; ?>
    </script>
</body>
</html>