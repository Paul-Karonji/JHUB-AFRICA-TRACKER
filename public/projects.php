<?php
// public/projects.php
// Public Projects Gallery
require_once '../includes/init.php';

// Get active projects
$projects = $database->getRows("
    SELECT p.*,
           COUNT(DISTINCT pm.mentor_id) as mentor_count,
           COUNT(DISTINCT pi.pi_id) as team_count
    FROM projects p
    LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
    LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
    WHERE p.status = 'active'
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
");

$pageTitle = "Innovation Projects";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/public.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-rocket me-2"></i>JHUB AFRICA
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Login
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../auth/admin-login.php">Admin Login</a></li>
                            <li><a class="dropdown-item" href="../auth/mentor-login.php">Mentor Login</a></li>
                            <li><a class="dropdown-item" href="../auth/project-login.php">Project Login</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="../applications/submit.php">
                            Apply for Program
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Innovation Projects</h1>
            <p class="lead text-muted">
                Explore groundbreaking innovations being developed through JHUB AFRICA
            </p>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($projects)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                    <h3>No Active Projects Yet</h3>
                    <p class="text-muted">Check back soon to see innovative projects!</p>
                    <a href="../applications/submit.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Submit Your Project
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 project-card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?php echo e($project['project_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                                    <span class="badge bg-success"><?php echo ucfirst($project['status']); ?></span>
                                </div>
                                
                                <p class="card-text"><?php echo truncateText(e($project['description']), 150); ?></p>
                                
                                <div class="project-meta text-muted small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-users me-1"></i> <?php echo $project['team_count']; ?> Team Members</span>
                                        <span><i class="fas fa-user-tie me-1"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar me-1"></i> Started <?php echo formatDate($project['created_at'], 'M Y'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="project-details.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h2 class="mb-3">Have an Innovation Idea?</h2>
            <p class="lead mb-4">Join JHUB AFRICA and bring your innovation to life through our structured development program</p>
            <a href="../applications/submit.php" class="btn btn-light btn-lg">
                <i class="fas fa-rocket me-2"></i>Apply Now
            </a>
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
                    <a href="about.php" class="text-white-50 me-3">About</a>
                    <a href="contact.php" class="text-white-50 me-3">Contact</a>
                    <a href="projects.php" class="text-white-50">Projects</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>