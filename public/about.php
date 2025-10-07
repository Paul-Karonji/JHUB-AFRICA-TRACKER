<?php
// public/about.php
// About JHUB AFRICA Page
require_once '../includes/init.php';

$pageTitle = "About Us";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - JHUB AFRICA</title>
    <meta name="description" content="Learn about JHUB AFRICA's mission to nurture African innovations from conception to market success through mentorship, resources, and community support.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-about {
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        .stage-timeline {
            position: relative;
            padding-left: 50px;
        }
        .stage-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #3b54c7 0%, #0e015b 100%);
        }
        .stage-item {
            position: relative;
            margin-bottom: 30px;
        }
        .stage-item::before {
            content: attr(data-stage);
            position: absolute;
            left: -38px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #2c409a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2c409a;
        }
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #2c409a;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/submit.php">Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-about text-center">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">About JHUB AFRICA</h1>
            <p class="lead mb-0 col-lg-8 mx-auto">
                We are Africa's premier innovation acceleration platform, dedicated to nurturing groundbreaking 
                African innovations from conception to market success through structured mentorship, 
                resource sharing, and community collaboration.
            </p>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="container my-5 py-5">
        <div class="row g-5">
            <div class="col-md-6">
                <div class="feature-card bg-primary text-white">
                    <div class="feature-icon bg-white text-primary">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="text-center mb-3">Our Mission</h3>
                    <p class="text-center mb-0">
                        To systematically nurture African innovations by providing structured mentorship, 
                        essential resources, and a supportive ecosystem that transforms innovative ideas 
                        into market-ready solutions.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card bg-success text-white">
                    <div class="feature-icon bg-white text-success">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="text-center mb-3">Our Vision</h3>
                    <p class="text-center mb-0">
                        To become the leading innovation development platform in Africa, creating a 
                        thriving ecosystem where African innovators can access world-class mentorship, 
                        resources, and networks to build solutions that transform the continent.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- What We Do -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">What We Do</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h5 class="text-center mb-3">Expert Mentorship</h5>
                        <p class="text-center">
                            Connect innovators with experienced mentors who provide guidance, 
                            industry insights, and practical support throughout the development journey.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5 class="text-center mb-3">Resource Sharing</h5>
                        <p class="text-center">
                            Provide access to educational materials, tools, industry contacts, 
                            and documentation needed to accelerate innovation development.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="text-center mb-3">Structured Progress</h5>
                        <p class="text-center">
                            Guide projects through a proven 6-stage development framework with 
                            assessments, learning objectives, and milestone tracking.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Development Stages -->
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">Our 6-Stage Development Framework</h2>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="stage-timeline">
                    <div class="stage-item" data-stage="1">
                        <h5>Stage 1: Project Creation</h5>
                        <p class="text-muted">
                            Initial project setup and team building. Projects enter our ecosystem 
                            and begin assembling their core team.
                        </p>
                    </div>
                    <div class="stage-item" data-stage="2">
                        <h5>Stage 2: Mentorship</h5>
                        <p class="text-muted">
                            Mentor assignment and initial guidance. Expert mentors join projects 
                            to provide strategic direction and support.
                        </p>
                    </div>
                    <div class="stage-item" data-stage="3">
                        <h5>Stage 3: Assessment</h5>
                        <p class="text-muted">
                            Market validation, business model verification, and technical feasibility 
                            reviews to ensure project viability.
                        </p>
                    </div>
                    <div class="stage-item" data-stage="4">
                        <h5>Stage 4: Learning and Development</h5>
                        <p class="text-muted">
                            Skill building and knowledge acquisition through targeted learning 
                            objectives and training programs.
                        </p>
                    </div>
                    <div class="stage-item" data-stage="5">
                        <h5>Stage 5: Progress Tracking</h5>
                        <p class="text-muted">
                            Regular milestone reviews, feedback collection, and performance optimization 
                            to ensure continuous improvement.
                        </p>
                    </div>
                    <div class="stage-item" data-stage="6">
                        <h5>Stage 6: Showcase and Integration</h5>
                        <p class="text-muted">
                            Final presentation, industry connections, and ecosystem integration 
                            to prepare for market launch.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="bg-dark text-white py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose JHUB AFRICA?</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number text-white">100%</div>
                        <div>Dedicated to African Innovation</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number text-white">6</div>
                        <div>Structured Development Stages</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number text-white">âˆž</div>
                        <div>Expert Mentor Network</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number text-white">24/7</div>
                        <div>Platform Access</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits -->
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">Benefits for Stakeholders</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>For Innovators</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Access to expert mentors</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Structured development framework</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Resource library and tools</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Networking opportunities</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Progress tracking and feedback</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Investor connections</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-handshake me-2"></i>For Partners & Investors</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Vetted innovation pipeline</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Transparent progress tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Direct access to innovators</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Quality assurance process</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Impact measurement</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Partnership opportunities</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-primary text-white py-5">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Join Our Innovation Ecosystem?</h2>
            <p class="lead mb-4">
                Whether you're an innovator with a groundbreaking idea, a mentor wanting to give back, 
                or an investor looking for opportunities, we'd love to connect with you.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="../applications/submit.php" class="btn btn-light btn-lg">
                    <i class="fas fa-rocket me-2"></i>Apply as Innovator
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-envelope me-2"></i>Get In Touch
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JHUB AFRICA</h5>
                    <p class="mb-0">Nurturing African Innovations from Conception to Market Success</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="projects.php" class="text-white-50">Projects</a></li>
                        <li><a href="about.php" class="text-white-50">About</a></li>
                        <li><a href="contact.php" class="text-white-50">Contact</a></li>
                        <li><a href="../applications/submit.php" class="text-white-50">Apply</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Connect</h6>
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-secondary my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
