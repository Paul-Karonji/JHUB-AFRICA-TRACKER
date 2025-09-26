<?php
// public/contact.php
require_once '../includes/init.php';
$pageTitle = "Contact Us";
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
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
                </ul>
                <a class="btn btn-outline-light" href="../applications/submit.php">Apply Now</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="mb-4">Contact Us</h1>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                <h5>Email</h5>
                                <p class="mb-0">support@jhubafrica.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                                <h5>Location</h5>
                                <p class="mb-0">Nairobi, Kenya</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Get in Touch</h5>
                        <p>Have questions about the JHUB AFRICA program? Want to learn more about how we can support your innovation? Reach out to us!</p>
                        
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><strong>For Application Inquiries:</strong> applications@jhubafrica.com</li>
                            <li class="mb-2"><strong>For Mentor Registration:</strong> mentors@jhubafrica.com</li>
                            <li class="mb-2"><strong>General Support:</strong> support@jhubafrica.com</li>
                        </ul>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <h4>Ready to Get Started?</h4>
                    <a href="../applications/submit.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-rocket me-2"></i>Apply for Program
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