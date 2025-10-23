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

    <!-- Favicon - JHUB Logo -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon_io/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon_io/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon_io/apple-touch-icon.png">
    <link rel="manifest" href="../assets/favicon_io/site.webmanifest">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .auth-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 50%, #0aba8a 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .auth-body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .auth-container {
            width: 100%;
            max-width: 1200px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease-out;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2),
                        0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auth-card:hover {
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25),
                        0 15px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }

        .auth-logo-img {
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto 25px;
            animation: scaleIn 0.6s ease-out 0.2s both;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .auth-logo-img:hover {
            transform: scale(1.05);
        }

        .auth-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);
            animation: scaleIn 0.6s ease-out 0.4s both;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .auth-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .auth-icon:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 15px 40px rgba(17, 153, 142, 0.5);
        }

        .auth-icon i {
            color: white;
            font-size: 2.2rem;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .auth-icon:hover i {
            transform: scale(1.1);
        }

        .auth-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 2rem;
            animation: fadeInUp 0.6s ease-out 0.6s both;
            letter-spacing: -0.5px;
        }

        .auth-header p {
            color: #718096;
            font-size: 1.05rem;
            animation: fadeInUp 0.6s ease-out 0.7s both;
            font-weight: 400;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .form-label i {
            color: #11998e;
            transition: transform 0.3s ease;
        }

        .mb-3:focus-within .form-label,
        .mb-4:focus-within .form-label {
            color: #11998e;
        }

        .mb-3:focus-within .form-label i,
        .mb-4:focus-within .form-label i {
            transform: scale(1.2);
        }

        .form-control {
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
        }

        .form-control:hover {
            border-color: #cbd5e0;
            background: #fff;
        }

        .form-control:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.15);
            background: #fff;
            transform: translateY(-2px);
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn-success {
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            color: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.3);
        }

        .btn-success::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-success:hover::before {
            left: 100%;
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(17, 153, 142, 0.4);
            color: white;
        }

        .btn-success:active {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(17, 153, 142, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
            padding: 14px 18px;
        }

        .btn-outline-secondary:hover {
            background: #11998e;
            border-color: #11998e;
            color: white;
        }

        .btn-outline-secondary:hover i {
            transform: scale(1.2);
        }

        .auth-footer {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }

        .auth-footer a {
            text-decoration: none;
            color: #11998e;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .auth-footer a:hover {
            color: #38ef7d;
            transform: translateX(3px);
        }

        .auth-footer a i {
            transition: transform 0.3s ease;
        }

        .auth-footer a:hover i {
            transform: translateX(3px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 22px;
            animation: fadeInUp 0.5s ease-out;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(254, 202, 202, 0.95) 0%, rgba(252, 165, 165, 0.95) 100%);
            color: #991b1b;
        }

        .auth-form {
            animation: fadeInUp 0.6s ease-out 0.9s both;
        }

        @media (max-width: 768px) {
            .auth-card {
                padding: 35px 25px;
                border-radius: 20px;
            }

            .auth-logo-img {
                max-width: 140px;
            }

            .auth-icon {
                width: 75px;
                height: 75px;
            }

            .auth-icon i {
                font-size: 1.8rem;
            }

            .auth-header h2 {
                font-size: 1.6rem;
            }

            .form-control {
                font-size: 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
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