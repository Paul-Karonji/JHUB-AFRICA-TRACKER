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
                            <i class="fas fa-users fa-3x text-info"></i>
                        </div>
                        <h2 class="mb-2">Project Login</h2>
                        <p class="text-muted">Sign in to your project dashboard</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <?php echo Validator::csrfInput(); ?>
                        
                        <div class="mb-3">
                            <label for="profile_name" class="form-label">
                                <i class="fas fa-project-diagram me-2"></i>Project Profile Name
                            </label>
                            <input type="text" class="form-control" id="profile_name" name="profile_name" 
                                   value="<?php echo e($profile_name); ?>" required autofocus
                                   placeholder="Enter your project profile name">
                            <div class="form-text">
                                This was provided when your project was approved.
                            </div>
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
                            <button type="submit" class="btn btn-info btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="auth-footer text-center mt-4">
                        <p class="mb-2">
                            <small class="text-muted">
                                Don't have project credentials yet?<br>
                                Your project must be approved first.
                            </small>
                        </p>
                        <a href="../applications/submit.php" class="btn btn-sm btn-outline-primary me-2">
                            Apply for Program
                        </a>
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