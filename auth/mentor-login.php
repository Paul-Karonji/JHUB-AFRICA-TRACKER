<?php
// auth/mentor-login.php
// Mentor Login Form
require_once '../includes/init.php';

// If already logged in as mentor, redirect to dashboard
if ($auth->isValidSession() && $auth->getUserType() === USER_TYPE_MENTOR) {
    redirect('/dashboards/mentor/index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Validator::validateCSRF()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        $validator = new Validator($_POST);
        $validator->required('email', 'Email is required')
                 ->email('email')
                 ->required('password', 'Password is required');
        
        if ($validator->isValid()) {
            $result = $auth->loginMentor($email, $password);
            
            if ($result['success']) {
                logActivity(USER_TYPE_MENTOR, $auth->getUserId(), 'login', 'Mentor login successful');
                setFlashMessage($result['message'], 'success');
                redirect('/dashboards/mentor/index.php');
            } else {
                $error = $result['message'];
                logActivity('system', null, 'failed_login', "Failed mentor login attempt for email: {$email}");
            }
        } else {
            $error = 'Please provide a valid email and password.';
        }
    }
}

$pageTitle = "Mentor Login";
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            border-color: #11998e;
            box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.1);
        }
        
        .btn-success {
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            transition: transform 0.2s;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.3);
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
        
        .auth-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .auth-footer a {
            text-decoration: none;
            color: #11998e;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .auth-footer a:hover {
            color: #38ef7d;
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
                        
                        <!-- Mentor Icon -->
                        <div class="auth-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        
                        <h2>Mentor Login</h2>
                        <p>Sign in to your mentor account</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form mt-4">
                        <?php echo Validator::csrfInput(); ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo e($email); ?>" required autofocus
                                   placeholder="Enter your email address">
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
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="auth-footer text-center">
                        <a href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login Options
                        </a>
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