<?php
// templates/header.php - Main Header Template
// This file should be included at the top of every page

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user info
$currentUserType = $auth->getUserType() ?? null;
$currentUserId = $auth->getUserId() ?? null;
$currentUserName = $auth->getUserIdentifier() ?? 'Guest';

// Get pending comments count for admin badge
$pendingCommentsCount = 0;
if ($currentUserType === USER_TYPE_ADMIN) {
    $pendingCommentsCount = $database->count(
        'comments', 
        'is_deleted = 0 AND is_approved = 0'
    );
}

// Determine user role for styling
$userRole = '';
$userRoleClass = '';
$dashboardLink = '#';

if ($currentUserType === USER_TYPE_ADMIN) {
    $userRole = 'Admin';
    $userRoleClass = 'admin-theme';
    $dashboardLink = '/dashboards/admin/index.php';
} elseif ($currentUserType === USER_TYPE_MENTOR) {
    $userRole = 'Mentor';
    $userRoleClass = 'mentor-theme';
    $dashboardLink = '/dashboards/mentor/index.php';
} elseif ($currentUserType === USER_TYPE_PROJECT) {
    $userRole = 'Project';
    $userRoleClass = 'project-theme';
    $dashboardLink = '/dashboards/project/index.php';
}

// Get base URL
$baseUrl = SITE_URL ?? 'http://localhost/jhub-africa-tracker';
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME;

// Logo path configuration - CENTRALIZED
$logoPath = $baseUrl . '/assets/images/logo/JHUB Africa Logo.png';
$logoAlt = 'JHUB AFRICA - Innovations for Transformation';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo e($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/main.css">
    
    <!-- Custom page-specific styles -->
    <?php if (isset($customStyles)): ?>
        <?php echo $customStyles; ?>
    <?php endif; ?>
    
    <style>
        /* Global Styles */
        :root {
            --admin-color: #2c409a;
            --mentor-color: #3fa845;
            --project-color: #0e015b;
            --bs-primary: #2c409a;
            --bs-primary-rgb: 44, 64, 154;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c409a 0%, #0e015b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        /* UPDATED: Logo Styles */
        .sidebar-brand {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255, 255, 255, 0.05);
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-brand .logo-container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-brand img {
            max-width: 180px;
            height: auto;
            max-height: 60px;
            object-fit: contain;
            transition: all 0.3s ease;
            display: block;
        }
        
        .sidebar.collapsed .sidebar-brand img {
            max-width: 50px;
            max-height: 50px;
        }
        
        .sidebar.collapsed .sidebar-brand {
            padding: 15px 10px;
        }
        
        /* Logo fallback */
        .sidebar-brand .logo-fallback {
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            line-height: 1.3;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            text-decoration: none;
            position: relative;
        }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--bs-primary);
        }
        
        .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: var(--bs-primary);
        }
        
        .sidebar-nav .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .sidebar.collapsed .nav-link span:not(.badge) {
            display: none;
        }
        
        /* Badge Styles */
        .nav-link .badge {
            font-size: 0.7rem;
            padding: 0.25em 0.6em;
            border-radius: 10px;
            margin-left: auto;
        }
        
        .sidebar.collapsed .nav-link .badge {
            position: absolute;
            right: 5px;
            top: 5px;
            transform: scale(0.8);
        }
        
        /* Badge Animation */
        @keyframes pulse-warning {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }
        
        .nav-link .badge.bg-warning {
            animation: pulse-warning 2s infinite;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: margin-left 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .content-wrapper {
            padding: 30px;
            flex: 1;
        }
        
        /* User Badge */
        .user-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .admin-theme .user-badge {
            background: rgba(44, 64, 154, 0.1);
            color: var(--admin-color);
        }
        
        .mentor-theme .user-badge {
            background: rgba(63, 168, 69, 0.1);
            color: var(--mentor-color);
        }
        
        .project-theme .user-badge {
            background: rgba(14, 1, 91, 0.1);
            color: var(--project-color);
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            /* Adjust logo for tablet */
            .sidebar-brand img {
                max-width: 160px;
                max-height: 55px;
            }
        }
        
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 15px;
            }
            
            .top-navbar {
                padding: 10px 15px;
            }
            
            /* Adjust logo for mobile */
            .sidebar-brand img {
                max-width: 140px;
                max-height: 45px;
            }
            
            .sidebar-brand {
                padding: 12px 15px;
                min-height: 70px;
            }
        }
    </style>
</head>
<body class="<?php echo $userRoleClass; ?>">
    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Logo Section -->
            <div class="sidebar-brand">
                <a href="<?php echo $baseUrl . $dashboardLink; ?>" class="logo-container">
                    <img src="<?php echo $logoPath; ?>" 
                         alt="<?php echo $logoAlt; ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="logo-fallback" style="display: none;">
                        JHUB<br>AFRICA
                    </div>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <?php if ($currentUserType === USER_TYPE_ADMIN): ?>
                    <!-- Admin Navigation -->
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/applications.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Applications</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/projects.php" class="nav-link">
                        <i class="fas fa-project-diagram"></i>
                        <span>Projects</span>
                    </a>
                    
                    <!-- MODERATION LINK - NEW -->
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/moderate-comments.php" class="nav-link">
                        <i class="fas fa-gavel"></i>
                        <span>Moderate Comments</span>
                        <?php if ($pendingCommentsCount > 0): ?>
                            <span class="badge bg-warning text-dark"><?php echo $pendingCommentsCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/mentors.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Mentors</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/register-mentor.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Mentor</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/admin-management.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        <span>Admins</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                
                <?php elseif ($currentUserType === USER_TYPE_MENTOR): ?>
                    <!-- Mentor Navigation -->
                       <a href="<?php echo $baseUrl; ?>/dashboards/mentor/index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/available-projects.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Browse Projects</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/my-projects.php" class="nav-link">
                            <i class="fas fa-project-diagram"></i>
                            <span>My Projects</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/resources.php" class="nav-link">
                            <i class="fas fa-book"></i>
                            <span>Resources</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/assessments.php" class="nav-link">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Assessments</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/learning.php" class="nav-link">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Learning</span>
                        </a>
                        
                        <!-- Divider -->
                        <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0;"></div>
                        
                        <a href="<?php echo $baseUrl; ?>/dashboards/mentor/profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                
                <?php elseif ($currentUserType === USER_TYPE_PROJECT): ?>
                    <!-- Project Navigation -->
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/team.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Team</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/mentors.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Mentors</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/resources.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Resources</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/assessments.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Assessments</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/learning.php" class="nav-link">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Learning</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/progress.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/profile.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                <?php endif; ?>
                
                <!-- Common Links -->
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
                <a href="<?php echo $baseUrl; ?>/public/projects.php" class="nav-link" target="_blank">
                    <i class="fas fa-globe"></i>
                    <span>Public Projects</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/auth/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="d-flex align-items-center">
                    <button class="mobile-menu-toggle me-3" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="btn btn-link text-dark d-none d-lg-block" onclick="toggleSidebar()" title="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($pendingCommentsCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $pendingCommentsCount; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if ($pendingCommentsCount > 0): ?>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/dashboards/admin/moderate-comments.php">
                                <i class="fas fa-gavel text-warning"></i> <?php echo $pendingCommentsCount; ?> comment(s) pending approval
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#">New application submitted</a></li>
                            <li><a class="dropdown-item" href="#">Project update available</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View All</a></li>
                        </ul>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <span class="user-badge"><?php echo e($userRole); ?></span>
                            <span class="d-none d-md-inline"><?php echo e($currentUserName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Account</h6></li>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl . $dashboardLink; ?>">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/dashboards/<?php echo strtolower($userRole); ?>/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-wrapper">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['flash_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                endif; 
                ?>