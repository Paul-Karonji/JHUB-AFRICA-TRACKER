<?php
/**
 * templates/public-header.php
 * Public-facing header for non-authenticated users
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get base URL
$baseUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/jhub-africa-tracker';
$siteName = defined('SITE_NAME') ? SITE_NAME : 'JHUB AFRICA';
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . $siteName : $siteName;

// Logo path configuration
$logoPath = $baseUrl . '/assets/images/logo/JHUB Africa Logo.png';
$logoAlt = 'JHUB AFRICA - Innovations for Transformation';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    
    <!-- Custom Public CSS -->
    <style>
        /* Public Theme Styles */
        :root {
            --primary-color: #2c409a;
            --secondary-color: #0e015b;
            --accent-color: #3fa845;
            --light-bg: #f8f9fa;
            --dark-text: #333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
            padding-top: 80px; /* Height of fixed navbar */
        }
        
        /* Public Navbar */
        .public-navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 0;
        }
        
        .public-navbar .container {
            padding: 0.5rem 1rem;
        }
        
        .public-navbar .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0;
            margin: 0;
        }
        
        .public-navbar .navbar-brand img {
            max-height: 50px;
            width: auto;
            transition: all 0.3s ease;
        }
        
        .public-navbar .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .public-navbar .navbar-nav {
            align-items: center;
        }
        
        .public-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            transition: all 0.3s;
            border-radius: 5px;
            margin: 0 2px;
        }
        
        .public-navbar .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .public-navbar .nav-link.active {
            color: white !important;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .public-navbar .btn-login {
            background: white;
            color: var(--primary-color);
            border: 2px solid white;
            font-weight: 600;
            padding: 8px 24px;
            border-radius: 25px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .public-navbar .btn-login:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,255,255,0.3);
        }
        
        .public-navbar .btn-apply {
            background: var(--accent-color);
            color: white;
            border: 2px solid var(--accent-color);
            font-weight: 600;
            padding: 8px 24px;
            border-radius: 25px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .public-navbar .btn-apply:hover {
            background: transparent;
            border-color: white;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,255,255,0.3);
        }
        
        /* Mobile Menu */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
            padding: 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            width: 1.5em;
            height: 1.5em;
        }
        
        /* Main Content */
        main.public-content {
            min-height: calc(100vh - 80px);
            padding-bottom: 40px;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 5px;
            padding: 10px 24px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Footer */
        .public-footer {
            background: var(--secondary-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        
        .public-footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .public-footer a:hover {
            color: white;
        }
        
        .public-footer .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .public-footer .social-links a:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .public-navbar .navbar-brand img {
                max-height: 40px;
            }
            
            .public-navbar .btn-login,
            .public-navbar .btn-apply {
                width: 100%;
                margin: 5px 0;
                text-align: center;
            }
            
            .public-navbar .navbar-collapse {
                background: rgba(0, 0, 0, 0.1);
                padding: 15px;
                border-radius: 8px;
                margin-top: 10px;
            }
        }
    </style>
    
    <!-- Custom page-specific styles -->
    <?php if (isset($customStyles)): ?>
        <?php echo $customStyles; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Public Navigation Bar -->
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>/public/index.php">
                <img src="<?php echo $logoPath; ?>" 
                     alt="<?php echo $logoAlt; ?>"
                     onerror="this.style.display='none'; this.parentElement.innerHTML+='<span style=\'color:white;font-weight:bold;font-size:1.2rem;\'>JHUB AFRICA</span>';">
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/public/index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/public/projects.php">
                            <i class="fas fa-project-diagram me-1"></i> Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/public/about.php">
                            <i class="fas fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/public/contact.php">
                            <i class="fas fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                    
                    <!-- Divider for mobile -->
                    <li class="nav-item d-lg-none">
                        <hr style="border-color: rgba(255,255,255,0.2); margin: 10px 0;">
                    </li>
                    
                    <!-- Apply Button -->
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-apply" href="<?php echo $baseUrl; ?>/applications/submit.php">
                            <i class="fas fa-paper-plane me-1"></i> Apply Now
                        </a>
                    </li>
                    
                    <!-- Login Button -->
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-login" href="<?php echo $baseUrl; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="public-content">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php 
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        endif; 
        ?>