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
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="auth-header text-center">
                        <div class="auth-logo mb-4">
                            <i class="fas fa-user-tie fa-3x text-success"></i>
                        </div>
                        <h2 class="mb-2">Mentor Login</h2>
                        <p class="text-muted">Sign in to your mentor account</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <?php echo Validator::csrfInput(); ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo e($email); ?>" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
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
                    
                    <div class="auth-footer text-center mt-4">
                        <a href="login.php" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login Options
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
</body>
</html>