<?php
// index.php - Main Landing Page
require_once 'includes/init.php';

// If user is logged in, redirect to appropriate dashboard
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

// Get public statistics
$stats = getSystemStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Nurturing African Innovation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/public.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-rocket me-2"></i>
                JHUB AFRICA
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/contact.php">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Login
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="auth/admin-login.php">Admin Login</a></li>
                            <li><a class="dropdown-item" href="auth/mentor-login.php">Mentor Login</a></li>
                            <li><a class="dropdown-item" href="auth/project-login.php">Project Login</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="applications/submit.php">
                            Apply for Program
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-gradient text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Nurturing African Innovation</h1>
                    <p class="lead mb-4">
                        JHUB AFRICA is a comprehensive innovation management platform that guides African innovations 
                        through a structured 6-stage development journey, connecting innovators with mentors, 
                        resources, and investment opportunities.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="applications/submit.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Submit Your Project
                        </a>
                        <a href="public/projects.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-eye me-2"></i>View Projects
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-stats row text-center">
                        <div class="col-4">
                            <div class="stat-card">
                                <h3 class="display-6 fw-bold"><?php echo $stats['active_projects']; ?></h3>
                                <p class="mb-0">Active Projects</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <h3 class="display-6 fw-bold"><?php echo $stats['total_mentors']; ?></h3>
                                <p class="mb-0">Expert Mentors</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <h3 class="display-6 fw-bold"><?php echo $stats['completed_projects']; ?></h3>
                                <p class="mb-0">Success Stories</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Innovation Framework -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our 6-Stage Innovation Framework</h2>
            <div class="row">
                <?php
                for ($stage = 1; $stage <= 6; $stage++) {
                    $stageName = getStageName($stage);
                    $stageDesc = getStageDescription($stage);
                    $progress = getStageProgress($stage);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 stage-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <span class="badge bg-light text-primary me-2">Stage <?php echo $stage; ?></span>
                                <?php echo $stageName; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo $stageDesc; ?></p>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">How JHUB AFRICA Works</h2>
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <div class="process-step">
                        <div class="step-icon">
                            <i class="fas fa-paper-plane fa-3x text-primary"></i>
                        </div>
                        <h4>1. Apply</h4>
                        <p>Submit your innovation idea with project details and presentation.</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="process-step">
                        <div class="step-icon">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h4>2. Get Approved</h4>
                        <p>Our team reviews and approves viable innovation projects.</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="process-step">
                        <div class="step-icon">
                            <i class="fas fa-user-friends fa-3x text-info"></i>
                        </div>
                        <h4>3. Get Mentorship</h4>
                        <p>Connect with expert mentors who guide your development journey.</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="process-step">
                        <div class="step-icon">
                            <i class="fas fa-trophy fa-3x text-warning"></i>
                        </div>
                        <h4>4. Showcase & Scale</h4>
                        <p>Present your innovation and connect with investors and partners.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JHUB AFRICA</h5>
                    <p class="mb-0">Empowering African innovation through structured mentorship and support.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="public/about.php" class="text-white-50 me-3">About</a>
                    <a href="public/contact.php" class="text-white-50 me-3">Contact</a>
                    <a href="public/projects.php" class="text-white-50">Projects</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

