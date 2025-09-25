<?php
// auth/login.php
// Universal Login Page
require_once '../includes/init.php';

// If already logged in, redirect to dashboard
if ($auth->isValidSession()) {
    $userType = $auth->getUserType();
    switch ($userType) {
        case USER_TYPE_ADMIN:
            redirect('/dashboards/admin/index.php');
            break;
        case USER_TYPE_MENTOR:
            redirect('/dashboards/mentor/index.php');
            break;
        case USER_TYPE_PROJECT:
            redirect('/dashboards/project/index.php');
            break;
    }
}

$pageTitle = "Login - Choose Your Role";
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
            <div class="col-md-8 col-lg-6">
                <div class="auth-card">
                    <div class="auth-header text-center">
                        <div class="auth-logo mb-4">
                            <i class="fas fa-rocket fa-3x text-primary"></i>
                        </div>
                        <h2 class="mb-2">Welcome to JHUB AFRICA</h2>
                        <p class="text-muted">Choose your login type to continue</p>
                    </div>
                    
                    <?php echo displayFlashMessages(); ?>
                    
                    <div class="login-options">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="admin-login.php" class="login-option-card">
                                    <div class="card h-100 text-center">
                                        <div class="card-body">
                                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title">Admin</h5>
                                            <p class="card-text">System administrators and project managers</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <a href="mentor-login.php" class="login-option-card">
                                    <div class="card h-100 text-center">
                                        <div class="card-body">
                                            <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                                            <h5 class="card-title">Mentor</h5>
                                            <p class="card-text">Expert mentors guiding innovation projects</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <a href="project-login.php" class="login-option-card">
                                    <div class="card h-100 text-center">
                                        <div class="card-body">
                                            <i class="fas fa-users fa-3x text-info mb-3"></i>
                                            <h5 class="card-title">Project Team</h5>
                                            <p class="card-text">Innovators and project team members</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="mb-2">Don't have access yet?</p>
                        <a href="../applications/submit.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus-circle me-2"></i>Apply for Program
                        </a>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>