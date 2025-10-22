<?php
// auth/project-login.php
// Project Login Form
require_once '../includes/init.php';

// If already logged in as project, redirect to dashboard
if ($auth->isValidSession() && $auth->getUserType() === USER_TYPE_PROJECT) {
    redirect('/dashboards/project/index.php');
}

$error = '';
$profile_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Validator::validateCSRF()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $profile_name = trim($_POST['profile_name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        $validator = new Validator($_POST);
        $validator->required('profile_name', 'Profile name is required')
                 ->required('password', 'Password is required');
        
        if ($validator->isValid()) {
            $result = $auth->loginProject($profile_name, $password);
            
            if ($result['success']) {
                logActivity(USER_TYPE_PROJECT, $auth->getUserId(), 'login', 'Project login successful');
                setFlashMessage($result['message'], 'success');
                redirect('/dashboards/project/index.php');
            } else {
                $error = $result['message'];
                logActivity('system', null, 'failed_login', "Failed project login attempt for profile: {$profile_name}");
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}

$pageTitle = "Project Login";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - JHUB AFRICA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/auth.css" rel="stylesheet">
    <style>
        .auth-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0093E9 0%, #80D0C7 100%);
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 1200px;
        }
        
        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            padding: 40px;
        }
        
        .auth-logo-img {
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto 20px;
        }
        
        .auth-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0093E9 0%, #80D0C7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .auth-icon i {
            color: white;
            font-size: 2rem;
        }
        
        .auth-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #718096;
            font-size: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #0093E9;
            box-shadow: 0 0 0 3px rgba(0, 147, 233, 0.1);
        }
        
        .form-text {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .btn-info {
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(135deg, #0093E9 0%, #80D0C7 100%);
            border: none;
            color: white;
            transition: transform 0.2s;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 147, 233, 0.3);
            color: white;
        }
        
        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            border-left: none;
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #f7fafc;
            border-color: #e2e8f0;
        }
        
        .btn-outline-primary {
            border: 2px solid #0093E9;
            color: #0093E9;
            border-radius: 8px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: #0093E9;
            color: white;
        }
        
        .auth-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .auth-footer a {
            text-decoration: none;
            color: #0093E9;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .auth-footer a:hover {
            color: #80D0C7;
        }
        
        .auth-footer .text-muted {
            color: #718096 !important;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        @media (max-width: 768px) {
            .auth-card {
                padding: 30px 20px;
            }
            
            .auth-logo-img {
                max-width: 140px;
            }
            
            .btn-outline-primary {
                margin-bottom: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="auth-card">
                    <div class="auth-header text-center">
                        <!-- JHUB Logo -->
                        <img src="<?php echo SITE_URL; ?>/assets/images/logo/JHUB Africa Logo.png" 
                             alt="JHUB AFRICA" 
                             class="auth-logo-img"
                             onerror="this.style.display='none';">
                        
                        <!-- Project Icon -->
                        <div class="auth-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        
                        <h2>Project Login</h2>
                        <p>Sign in to your project dashboard</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form mt-4">
                        <?php echo Validator::csrfInput(); ?>
                        
                        <div class="mb-3">
                            <label for="profile_name" class="form-label">
                                <i class="fas fa-project-diagram me-2"></i>Project Profile Name
                            </label>
                            <input type="text" class="form-control" id="profile_name" name="profile_name" 
                                   value="<?php echo e($profile_name); ?>" required autofocus
                                   placeholder="Enter your project profile name">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>This was provided when your project was approved.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required placeholder="Enter your password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-info btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="auth-footer text-center">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-question-circle me-1"></i>Don't have project credentials yet?
                            </small>
                            <small class="text-muted d-block mb-3">
                                Your project must be approved first.
                            </small>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                            <a href="<?php echo SITE_URL; ?>/applications/submit.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-rocket me-1"></i>Apply for Program
                            </a>
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login Options
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>  