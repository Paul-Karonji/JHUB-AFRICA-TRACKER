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
            --admin-color: #dc3545;
            --mentor-color: #17a2b8;
            --project-color: #28a745;
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
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
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
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .sidebar-brand img {
            max-width: 100%;
            height: auto;
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
        
        .sidebar.collapsed .nav-link span {
            display: none;
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
            background: rgba(220, 53, 69, 0.1);
            color: var(--admin-color);
        }
        
        .mentor-theme .user-badge {
            background: rgba(23, 162, 184, 0.1);
            color: var(--mentor-color);
        }
        
        .project-theme .user-badge {
            background: rgba(40, 167, 69, 0.1);
            color: var(--project-color);
        }
        
        /* Cards */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            margin-bottom: 1.5rem;
        }
        
        .border-left-primary {
            border-left: 4px solid #4e73df !important;
        }
        
        .border-left-success {
            border-left: 4px solid #1cc88a !important;
        }
        
        .border-left-info {
            border-left: 4px solid #36b9cc !important;
        }
        
        .border-left-warning {
            border-left: 4px solid #f6c23e !important;
        }
        
        .border-left-danger {
            border-left: 4px solid #e74a3b !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block !important;
            }
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Loading Spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .spinner-overlay.show {
            display: flex;
        }
    </style>
</head>
<body class="<?php echo $userRoleClass; ?>">
    
    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="globalSpinner">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="<?php echo $baseUrl; ?>/assets/images/logo.png" alt="JHUB AFRICA" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span style="display: none;">JHUB AFRICA</span>
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
                    <a href="<?php echo $baseUrl; ?>/dashboards/mentor/resources.php" class="nav-link">
                        <i class="fas fa-folder"></i>
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
                    <a href="<?php echo $baseUrl; ?>/dashboards/project/resources.php" class="nav-link">
                        <i class="fas fa-folder"></i>
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
                    <button class="btn btn-link text-dark" onclick="toggleSidebar()" title="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications (placeholder) -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark position-relative" type="button" 
                                id="notificationDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                  style="font-size: 0.6rem;">
                                0
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                        </ul>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark d-flex align-items-center text-decoration-none" 
                                type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <img src="<?php echo getGravatar($currentUserName, 32); ?>" 
                                 class="rounded-circle me-2" alt="User">
                            <div class="text-start d-none d-md-block">
                                <div class="fw-bold"><?php echo e($currentUserName); ?></div>
                                <small class="user-badge"><?php echo $userRole; ?></small>
                            </div>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?php echo e($currentUserName); ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $dashboardLink; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a></li>
                            <?php if ($currentUserType === USER_TYPE_ADMIN): ?>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/dashboards/admin/settings.php">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a></li>
                            <?php else: ?>
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/dashboards/<?php echo strtolower($userRole); ?>/profile.php">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <?php
                // Display flash messages if any
                if (isset($_SESSION['success_message'])):
                ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo e($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                    unset($_SESSION['success_message']);
                endif;

                if (isset($_SESSION['error_message'])):
                ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo e($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                    unset($_SESSION['error_message']);
                endif;
                ?>
                
                <!-- Main Content Goes Here -->