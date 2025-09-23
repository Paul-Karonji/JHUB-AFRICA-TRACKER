<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Main Entry Point / Landing Page
 * 
 * This is the public landing page that showcases JHUB AFRICA's mission
 * and displays the project gallery for public viewing.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/includes/init.php';

// Check if initialization was successful
if (!defined('JHUB_INITIALIZED')) {
    die('Application initialization failed');
}

try {
    // Initialize required classes
    $project = new Project();
    
    // Get public projects for display
    $projectsResult = $project->getAllProjects();
    $projects = $projectsResult['success'] ? $projectsResult['projects'] : [];
    
    // Get statistics for the hero section
    $stats = [
        'total_projects' => count($projects),
        'active_projects' => count(array_filter($projects, function($p) { return $p['status'] === 'active'; })),
        'completed_projects' => count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })),
        'mentors' => 0, // We'll get this from database
        'innovators' => 0 // We'll get this from database
    ];
    
    // Get mentor and innovator counts
    $db = Database::getInstance();
    $stats['mentors'] = $db->fetchColumn("SELECT COUNT(*) FROM mentors WHERE is_active = 1");
    $stats['innovators'] = $db->fetchColumn("SELECT COUNT(DISTINCT email) FROM project_innovators");
    
} catch (Exception $e) {
    logActivity('ERROR', 'Landing page error: ' . $e->getMessage());
    $projects = [];
    $stats = ['total_projects' => 0, 'active_projects' => 0, 'completed_projects' => 0, 'mentors' => 0, 'innovators' => 0];
}

// Set page title and meta
$pageTitle = 'JHUB AFRICA - Innovation Ecosystem';
$pageDescription = 'Empowering African innovators through structured support, expert mentorship, and community collaboration.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="innovation, africa, startup, mentorship, entrepreneurship, technology">
    <meta name="author" content="JHUB AFRICA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo AppConfig::getUrl(); ?>">
    <meta property="og:image" content="<?php echo AppConfig::getAsset('images/og-image.png'); ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo AppConfig::getAsset('images/og-image.png'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/public.css'); ?>">
    
    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="<?php echo AppConfig::generateCSRFToken(); ?>">
    
    <style>
        /* Inline critical CSS for faster loading */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8faff 100%);
        }
        
        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(21, 101, 192, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-placeholder {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .brand-text h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .brand-text p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .hero-section {
            padding: 4rem 0;
            text-align: center;
            background: linear-gradient(135deg, rgba(21,101,192,0.1) 0%, rgba(255,255,255,0.9) 100%);
            border-radius: 20px;
            margin: 2rem 0;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: #0d47a1;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            color: #1565c0;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(21, 101, 192, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1565c0;
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .cta-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            margin: 3rem 0;
            box-shadow: 0 15px 40px rgba(21, 101, 192, 0.1);
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary-solid {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
        }
        
        .btn-primary-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(21, 101, 192, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: #1565c0;
            border: 2px solid #1565c0;
        }
        
        .btn-secondary:hover {
            background: #1565c0;
            color: white;
        }
        
        .projects-preview {
            margin: 4rem 0;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .project-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(21, 101, 192, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(21, 101, 192, 0.15);
        }
        
        .project-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0d47a1;
            margin-bottom: 1rem;
        }
        
        .project-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-success { background: rgba(76, 175, 80, 0.1); color: #4caf50; }
        .badge-info { background: rgba(33, 150, 243, 0.1); color: #2196f3; }
        .badge-warning { background: rgba(255, 193, 7, 0.1); color: #ff9800; }
        
        .footer {
            background: #0d47a1;
            color: white;
            padding: 3rem 0 2rem 0;
            margin-top: 4rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: white;
        }
        
        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50vh;
            font-size: 1.2rem;
            color: #1565c0;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="header">
        <div class="container">
            <nav class="nav-container">
                <div class="logo-section">
                    <div class="logo-placeholder">JHUB</div>
                    <div class="brand-text">
                        <h1>JHUB AFRICA</h1>
                        <p>Innovation Ecosystem</p>
                    </div>
                </div>
                <div class="nav-links">
                    <a href="<?php echo AppConfig::getUrl(); ?>">Home</a>
                    <a href="<?php echo AppConfig::getUrl('public/projects.php'); ?>">Projects</a>
                    <a href="<?php echo AppConfig::getUrl('public/create-project.php'); ?>">Start Project</a>
                    <a href="<?php echo AppConfig::getLoginUrl(); ?>" class="btn-primary">Login</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Hero Section -->
            <section class="hero-section">
                <h1 class="hero-title">Empowering African Innovation</h1>
                <p class="hero-subtitle">
                    Join JHUB AFRICA's structured innovation journey. From idea to impact, 
                    we provide the mentorship, resources, and community you need to succeed.
                </p>
                
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['total_projects']); ?></span>
                        <div class="stat-label">Innovation Projects</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['mentors']); ?></span>
                        <div class="stat-label">Expert Mentors</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['innovators']); ?></span>
                        <div class="stat-label">Innovators</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo number_format($stats['completed_projects']); ?></span>
                        <div class="stat-label">Success Stories</div>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="cta-section">
                <h2>Ready to Start Your Innovation Journey?</h2>
                <p>Join thousands of African innovators who are building the future with JHUB AFRICA's support.</p>
                <div class="cta-buttons">
                    <a href="<?php echo AppConfig::getUrl('public/create-project.php'); ?>" class="btn btn-primary-solid">
                        Launch Your Project
                    </a>
                    <a href="<?php echo AppConfig::getUrl('public/projects.php'); ?>" class="btn btn-secondary">
                        Explore Projects
                    </a>
                </div>
            </section>

            <!-- Featured Projects -->
            <section class="projects-preview">
                <h2 style="text-align: center; color: #0d47a1; margin-bottom: 1rem;">
                    Featured Innovation Projects
                </h2>
                <p style="text-align: center; color: #666; margin-bottom: 3rem;">
                    Discover the cutting-edge solutions being developed across Africa
                </p>
                
                <div class="projects-grid">
                    <?php 
                    $featuredProjects = array_slice($projects, 0, 6); // Show max 6 projects
                    foreach ($featuredProjects as $project): 
                    ?>
                        <div class="project-card">
                            <h3 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h3>
                            <p class="project-description">
                                <?php 
                                $description = htmlspecialchars($project['description'] ?? '');
                                echo strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description; 
                                ?>
                            </p>
                            <div class="project-meta">
                                <span class="badge badge-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'warning'); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                                <span class="stage-info">
                                    Stage <?php echo $project['current_stage']; ?>
                                </span>
                            </div>
                            <a href="<?php echo AppConfig::getUrl('public/project-details.php?project_id=' . $project['id']); ?>" 
                               class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 1rem;">
                                Learn More
                            </a>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($featuredProjects)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #666;">
                            <h3>Projects Coming Soon</h3>
                            <p>Be the first to launch your innovation project on JHUB AFRICA!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($projects) > 6): ?>
                    <div style="text-align: center; margin-top: 3rem;">
                        <a href="<?php echo AppConfig::getUrl('public/projects.php'); ?>" class="btn btn-primary-solid">
                            View All Projects (<?php echo count($projects); ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About JHUB AFRICA</h3>
                    <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                        Empowering African innovators through structured support, expert mentorship, 
                        and community collaboration. Building the future of innovation across the continent.
                    </p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="<?php echo AppConfig::getUrl('public/projects.php'); ?>">Browse Projects</a>
                    <a href="<?php echo AppConfig::getUrl('public/create-project.php'); ?>">Start Your Project</a>
                    <a href="<?php echo AppConfig::getLoginUrl(); ?>">Login</a>
                    <a href="<?php echo AppConfig::getLoginUrl('mentor'); ?>">Mentor Portal</a>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <a href="mailto:<?php echo AppConfig::CONTACT_EMAIL; ?>">Contact Us</a>
                    <a href="mailto:<?php echo AppConfig::SUPPORT_EMAIL; ?>">Technical Support</a>
                    <a href="#">Documentation</a>
                    <a href="#">Community Forum</a>
                </div>
                <div class="footer-section">
                    <h3>Connect</h3>
                    <a href="<?php echo AppConfig::SOCIAL_LINKS['facebook']; ?>" target="_blank">Facebook</a>
                    <a href="<?php echo AppConfig::SOCIAL_LINKS['twitter']; ?>" target="_blank">Twitter</a>
                    <a href="<?php echo AppConfig::SOCIAL_LINKS['linkedin']; ?>" target="_blank">LinkedIn</a>
                    <a href="<?php echo AppConfig::SOCIAL_LINKS['instagram']; ?>" target="_blank">Instagram</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved. 
                   | Version <?php echo AppConfig::APP_VERSION; ?>
                   | Powered by Innovation
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Basic JavaScript for enhanced user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Add loading animation for project cards
            const projectCards = document.querySelectorAll('.project-card');
            projectCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Mobile menu handling (if needed for responsive design)
            const navToggle = document.querySelector('.nav-toggle');
            const navLinks = document.querySelector('.nav-links');
            
            if (navToggle && navLinks) {
                navToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }

            // Statistics counter animation
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent.replace(/,/g, ''));
                let current = 0;
                const increment = target / 50; // 50 steps for animation
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target.toLocaleString();
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current).toLocaleString();
                    }
                }, 40); // 40ms interval for smooth animation
            });
        });

        // Error handling for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
            });
        });

        // Add simple analytics (replace with your tracking code)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'page_view', {
                page_title: document.title,
                page_location: window.location.href
            });
        }
    </script>
</body>
</html>