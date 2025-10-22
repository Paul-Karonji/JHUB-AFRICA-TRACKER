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
        
        body.public-page {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
            padding-top: 110px;
        }
        
        /* Public Navbar */
        .public-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 1.2rem 0;
            transition: all 0.3s ease;
        }
        
        .public-navbar.scrolled {
            padding: 0.8rem 0;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
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
            max-height: 55px;
            width: auto;
            transition: all 0.3s ease;
        }
        
        .public-navbar.scrolled .navbar-brand img {
            max-height: 45px;
        }
        
        .public-navbar .navbar-nav {
            align-items: center;
        }
        
        .public-navbar .nav-link {
            color: var(--primary-color) !important;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 1.2rem !important;
            transition: all 0.3s ease;
            border-radius: 999px;
            margin: 0 4px;
            position: relative;
        }
        
        .public-navbar .nav-link::after {
            content: '';
            position: absolute;
            bottom: 6px;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--accent-color);
            border-radius: 3px;
            transform: translateX(-50%);
            transition: all 0.3s ease;
        }
        
        .public-navbar .nav-link:hover {
            color: var(--secondary-color) !important;
        }
        
        .public-navbar .nav-link:hover::after,
        .public-navbar .nav-link.active::after {
            width: 60%;
        }
        
        .public-navbar .btn-nav-cta {
            background: linear-gradient(135deg, var(--accent-color), #359a3b);
            color: white !important;
            font-weight: 700;
            padding: 0.6rem 1.6rem !important;
            margin-left: 1rem;
            border-radius: 999px;
            box-shadow: 0 5px 20px rgba(63, 168, 69, 0.3);
        }
        
        .public-navbar .btn-nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(63, 168, 69, 0.4);
        }
        
        .public-navbar .btn-login-link {
            border: 2px solid rgba(14, 1, 91, 0.15);
            color: var(--secondary-color) !important;
            font-weight: 600;
            padding: 0.55rem 1.4rem !important;
            border-radius: 999px;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .public-navbar .btn-login-link:hover {
            background: var(--secondary-color);
            color: white !important;
            border-color: var(--secondary-color);
        }
        
        .public-navbar .btn-nav-cta::after,
        .public-navbar .btn-login-link::after {
            display: none;
        }
        
        /* Mobile Menu */
        .navbar-toggler {
            border: none;
            padding: 0.35rem 0.6rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar-toggler-icon {
            filter: invert(20%);
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
        
        /* Responsive */
        @media (max-width: 768px) {
            body.public-page {
                padding-top: 80px;
            }
            
            .public-navbar {
                padding: 0.8rem 0;
            }
            
            .public-navbar .navbar-brand img {
                max-height: 42px;
            }
            
            .public-navbar .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                padding: 1rem;
                border-radius: 12px;
                margin-top: 0.75rem;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            }
            
            .public-navbar .btn-login-link,
            .public-navbar .btn-nav-cta {
                width: 100%;
                margin: 0.35rem 0 0;
                text-align: center;
            }
            
            .public-navbar .nav-link::after {
                display: none;
            }
        }
    </style>
    
    <!-- Custom page-specific styles -->
    <?php if (isset($customStyles)): ?>
        <?php echo $customStyles; ?>
    <?php endif; ?>
</head>
<body class="public-page">
    <!-- Public Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light public-navbar">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>/index.php">
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
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/index.php">
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
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link btn-nav-cta" href="<?php echo $baseUrl; ?>/applications/submit.php">
                            <i class="fas fa-paper-plane me-1"></i> Apply Now
                        </a>
                    </li>
                    
                    <!-- Login Button -->
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn-login-link" href="<?php echo $baseUrl; ?>/auth/login.php">
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
