<?php
// public/about.php
require_once '../includes/init.php';
$pageTitle = "About JHUB AFRICA";
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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-rocket me-2"></i>JHUB AFRICA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="projects.php">Projects</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <a class="btn btn-outline-light" href="../applications/submit.php">Apply Now</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="mb-4">About JHUB AFRICA</h1>
                
                <p class="lead">JHUB AFRICA is a comprehensive innovation management platform dedicated to nurturing African innovations through a structured 6-stage development journey.</p>
                
                <h3 class="mt-4">Our Mission</h3>
                <p>To empower African innovators by providing structured mentorship, resources, and support throughout their innovation journey, from concept to market-ready solutions.</p>
                
                <h3 class="mt-4">Our 6-Stage Framework</h3>
                <div class="row mt-3">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Stage <?php echo $i; ?>: <?php echo getStageName($i); ?></h5>
                                <p class="card-text"><?php echo getStageDescription($i); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <h3 class="mt-4">Why Choose JHUB AFRICA?</h3>
                <ul>
                    <li>Structured 6-stage development framework</li>
                    <li>Expert mentor guidance throughout the journey</li>
                    <li>Access to valuable resources and tools</li>
                    <li>Comprehensive progress tracking and feedback</li>
                    <li>Showcase opportunities to investors and partners</li>
                    <li>Supportive innovation ecosystem</li>
                </ul>
                
                <div class="text-center mt-5">
                    <a href="../applications/submit.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket me-2"></i>Start Your Innovation Journey
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>