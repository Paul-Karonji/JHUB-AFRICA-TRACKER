<?php
// index.php
// JHUB AFRICA Landing Page
require_once 'includes/init.php';

// Get latest projects
$latestProjects = $database->getRows("
    SELECT p.*,
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
    FROM projects p
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 6
");

// Get statistics
$stats = [
    'total_projects' => $database->count('projects', "status = 'active'"),
    'total_mentors' => $database->count('mentors', 'is_active = 1'),
    'total_innovators' => $database->count('project_innovators', 'is_active = 1'),
    'completed_projects' => $database->count('projects', "status = 'completed'")
];

$pageTitle = "Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHUB AFRICA - Nurturing African Innovations</title>
    <meta name="description" content="JHUB AFRICA is Africa's premier innovation acceleration platform. Join our ecosystem and transform your innovative ideas into market-ready solutions through expert mentorship and structured development.">
    <meta name="keywords" content="African innovation, startup incubator, mentorship, technology, innovation hub, Kenya, Africa">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
            opacity: 0.3;
        }
        .hero-content {
            position: relative;
            z-index: 1;
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
            transition: all 0.3s;
        }
        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            transition: all 0.3s;
            height: 100%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .project-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .project-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 30px;
        }
        .timeline-item::before {
            content: attr(data-step);
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        .btn-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
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
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2 px-3" href="applications/submit.php">
                            Apply Now
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInUp">
                        Transform Your <span class="text-warning">Innovation</span> Dreams Into Reality
                    </h1>
                    <p class="lead mb-4">
                        Africa's premier innovation acceleration platform. Get expert mentorship, 
                        access valuable resources, and join a thriving ecosystem of innovators.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="applications/submit.php" class="btn btn-warning btn-lg btn-pulse px-4">
                            <i class="fas fa-rocket me-2"></i>Apply Now - It's Free!
                        </a>
                        <a href="public/projects.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-eye me-2"></i>View Projects
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="opacity-75">
                            <i class="fas fa-check-circle me-2"></i>No fees  
                            <i class="fas fa-check-circle mx-2"></i>Expert mentors  
                            <i class="fas fa-check-circle mx-2"></i>Structured support
                        </small>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_projects']; ?>+</div>
                                <div class="text-muted">Active Projects</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_mentors']; ?>+</div>
                                <div class="text-muted">Expert Mentors</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_innovators']; ?>+</div>
                                <div class="text-muted">Innovators</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['completed_projects']; ?>+</div>
                                <div class="text-muted">Completed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 my-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Why Choose JHUB AFRICA?</h2>
                <p class="lead text-muted">Everything you need to turn your innovation into a success story</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4 class="mb-3">Expert Mentorship</h4>
                        <p class="text-muted">
                            Connect with experienced mentors who provide personalized guidance, 
                            industry insights, and practical support throughout your journey.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="mb-3">Structured Framework</h4>
                        <p class="text-muted">
                            Follow our proven 6-stage development process with clear milestones, 
                            assessments, and learning objectives to ensure steady progress.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="mb-3">Thriving Community</h4>
                        <p class="text-muted">
                            Join a network of innovators, mentors, and investors. Share knowledge, 
                            collaborate, and build lasting connections.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h4 class="mb-3">Resource Library</h4>
                        <p class="text-muted">
                            Access curated educational materials, tools, templates, and industry 
                            contacts to accelerate your development.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4 class="mb-3">Progress Tracking</h4>
                        <p class="text-muted">
                            Monitor your advancement with built-in assessments, feedback systems, 
                            and transparent progress indicators.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4 class="mb-3">Investor Connections</h4>
                        <p class="text-muted">
                            Showcase your innovation to potential investors, partners, and stakeholders 
                            through our public platform.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">How It Works</h2>
                <p class="lead text-muted">Your journey from idea to market-ready innovation in 6 stages</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="timeline">
                        <div class="timeline-item" data-step="1">
                            <h5>Apply & Get Approved</h5>
                            <p class="text-muted mb-0">
                                Submit your innovation idea through our application form. Our team reviews 
                                and approves viable projects within 5-7 days.
                            </p>
                        </div>
                        <div class="timeline-item" data-step="2">
                            <h5>Connect with Mentors</h5>
                            <p class="text-muted mb-0">
                                Expert mentors review active projects and join teams they're passionate about. 
                                Build your advisory team.
                            </p>
                        </div>
                        <div class="timeline-item" data-step="3">
                            <h5>Develop & Learn</h5>
                            <p class="text-muted mb-0">
                                Work through assessments, complete learning objectives, and build your 
                                innovation with mentor guidance.
                            </p>
                        </div>
                        <div class="timeline-item" data-step="4">
                            <h5>Track Progress</h5>
                            <p class="text-muted mb-0">
                                Monitor your advancement through our 6-stage framework. Receive feedback 
                                and iterate on your innovation.
                            </p>
                        </div>
                        <div class="timeline-item" data-step="5">
                            <h5>Showcase & Launch</h5>
                            <p class="text-muted mb-0">
                                Present your innovation to investors and partners. Get ready for market 
                                launch with full ecosystem support.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <?php if (!empty($latestProjects)): ?>
    <section class="py-5 my-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="display-5 fw-bold mb-2">Featured Innovations</h2>
                    <p class="text-muted mb-0">Discover the latest projects in our ecosystem</p>
                </div>
                <a href="public/projects.php" class="btn btn-outline-primary">
                    View All Projects <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="row g-4">
                <?php foreach (array_slice($latestProjects, 0, 3) as $project): ?>
                <div class="col-md-4">
                    <div class="project-card">
                        <div class="card-header">
                            <h5 class="mb-1 text-truncate"><?php echo e($project['project_name']); ?></h5>
                            <small>Stage <?php echo $project['current_stage']; ?> of 6</small>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted" style="min-height: 80px;">
                                <?php echo e(substr($project['description'], 0, 120)); ?>...
                            </p>
                            <div class="d-flex gap-3 mb-3 small text-muted">
                                <span><i class="fas fa-users me-1"></i><?php echo $project['team_count']; ?> Team</span>
                                <span><i class="fas fa-chalkboard-teacher me-1"></i><?php echo $project['mentor_count']; ?> Mentors</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo ($project['current_stage'] / 6) * 100; ?>%">
                                </div>
                            </div>
                            <a href="public/project-details.php?id=<?php echo $project['project_id']; ?>" 
                               class="btn btn-primary w-100">
                                View Details <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonials -->
    <section class="bg-dark text-white py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Success Stories</h2>
                <p class="lead opacity-75">Hear from innovators who transformed their ideas into reality</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "JHUB AFRICA's mentorship program was instrumental in helping us validate 
                            our business model and connect with investors. Highly recommended!"
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <strong>AM</strong>
                            </div>
                            <div>
                                <strong class="d-block">Amina Mwangi</strong>
                                <small class="text-muted">FinTech Innovator</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "The structured 6-stage framework kept us focused and accountable. We went from 
                            idea to pilot program in just 8 months!"
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <strong>JO</strong>
                            </div>
                            <div>
                                <strong class="d-block">James Ochieng</strong>
                                <small class="text-muted">AgriTech Founder</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted mb-4">
                            "The resource library and mentor network provided everything we needed. 
                            Best decision we made was joining JHUB AFRICA!"
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <strong>LN</strong>
                            </div>
                            <div>
                                <strong class="d-block">Linda Nduku</strong>
                                <small class="text-muted">HealthTech CEO</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-4 fw-bold mb-4">Ready to Transform Your Innovation?</h2>
            <p class="lead mb-5 col-lg-8 mx-auto">
                Join hundreds of African innovators who are building the future. Apply now and get 
                access to expert mentorship, valuable resources, and a supportive community.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="applications/submit.php" class="btn btn-warning btn-lg px-5 btn-pulse">
                    <i class="fas fa-rocket me-2"></i>Start Your Application
                </a>
                <a href="public/about.php" class="btn btn-outline-light btn-lg px-5">
                    <i class="fas fa-info-circle me-2"></i>Learn More
                </a>
            </div>
            <p class="mt-4 mb-0 opacity-75">
                <small>No application fees • Fast approval • Lifetime access</small>
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h4 class="mb-3">
                        <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
                    </h4>
                    <p class="mb-3">
                        Africa's premier innovation acceleration platform. Nurturing African innovations 
                        from conception to market success.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="public/projects.php" class="text-white-50 text-decoration-none">Projects</a></li>
                        <li class="mb-2"><a href="public/about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="public/contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="applications/submit.php" class="text-white-50 text-decoration-none">Apply</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="mb-3">For Users</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="auth/login.php" class="text-white-50 text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="auth/admin-login.php" class="text-white-50 text-decoration-none">Admin Login</a></li>
                        <li class="mb-2"><a href="auth/mentor-login.php" class="text-white-50 text-decoration-none">Mentor Login</a></li>
                        <li class="mb-2"><a href="auth/project-login.php" class="text-white-50 text-decoration-none">Project Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="mb-3">Contact Info</h6>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            info@jhubafrica.com
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            +254 XXX XXX XXX
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Nairobi, Kenya
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="bg-secondary my-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white-50 text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-white-50 text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-white-50 text-decoration-none">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" class="btn btn-primary position-fixed bottom-0 end-0 m-4" 
            style="display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to top functionality
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'block';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        });
        
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>