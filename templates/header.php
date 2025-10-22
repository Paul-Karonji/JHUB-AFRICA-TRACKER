<?php
// templates/header.php - Main Header Template with Enhanced Styling
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
        /* ===== JHUB AFRICA COLOR SCHEME ===== */
        :root {
            /* Primary Colors */
            --primary-blue: #2c409a;
            --alert-red: #fd1616;
            --success-green: #3fa845;
            --pure-black: #000000;
            --deep-purple: #0e015b;
            --pure-white: #ffffff;
            
            /* Role-specific colors */
            --admin-color: #2c409a;
            --mentor-color: #3fa845;
            --project-color: #0e015b;
            
            /* Bootstrap Override */
            --bs-primary: #2c409a;
            --bs-primary-rgb: 44, 64, 154;
            --bs-success: #3fa845;
            --bs-success-rgb: 63, 168, 69;
            --bs-danger: #fd1616;
            --bs-danger-rgb: 253, 22, 22;
            
            /* Neutral Colors */
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }
        
        /* ===== GLOBAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* ===== SIDEBAR STYLES ===== */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-blue) 0%, var(--deep-purple) 100%);
            color: var(--pure-white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        /* Logo Styles */
        .sidebar-brand {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            min-height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-brand .logo-container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .sidebar-brand img {
            max-width: 180px;
            height: auto;
            max-height: 60px;
            object-fit: contain;
            transition: all 0.3s ease;
            display: block;
            filter: brightness(0) invert(1); /* Make logo white */
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
            color: var(--pure-white);
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            line-height: 1.3;
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: absolute;
            right: -15px;
            top: 30px;
            width: 30px;
            height: 30px;
            background: var(--primary-blue);
            border: 2px solid var(--pure-white);
            border-radius: 50%;
            color: var(--pure-white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1001;
        }
        
        .sidebar-toggle:hover {
            background: var(--success-green);
            transform: scale(1.1);
        }
        
        /* Navigation Styles */
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            text-decoration: none;
            position: relative;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--pure-white);
            border-left-color: var(--success-green);
            padding-left: 25px;
        }
        
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--pure-white);
            border-left-color: var(--success-green);
            font-weight: 600;
        }
        
        .sidebar-nav .nav-link i {
            width: 24px;
            margin-right: 12px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar.collapsed .nav-link span:not(.badge) {
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 14px 10px;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }
        
        /* Badge Styles */
        .nav-link .badge {
            font-size: 0.7rem;
            padding: 0.3em 0.65em;
            border-radius: 12px;
            margin-left: auto;
            font-weight: 600;
        }
        
        .sidebar.collapsed .nav-link .badge {
            position: absolute;
            right: 8px;
            top: 8px;
            transform: scale(0.85);
        }
        
        /* Badge Animations */
        @keyframes pulse-badge {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.85;
                transform: scale(1.08);
            }
        }
        
        .nav-link .badge.bg-danger,
        .nav-link .badge.bg-warning {
            animation: pulse-badge 2s infinite;
        }
        
        /* Divider */
        .sidebar-nav hr {
            border-color: rgba(255, 255, 255, 0.15);
            margin: 20px 15px;
        }
        
        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--bg-light);
        }
        
        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }
        
        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            background: var(--pure-white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 3px solid var(--primary-blue);
        }
        
        .top-navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .top-navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            padding: 30px;
            flex: 1;
        }
        
        /* ===== USER BADGE ===== */
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .user-badge i {
            font-size: 1rem;
        }
        
        .admin-theme .user-badge {
            background: linear-gradient(135deg, rgba(44, 64, 154, 0.1), rgba(44, 64, 154, 0.15));
            color: var(--admin-color);
            border: 2px solid rgba(44, 64, 154, 0.2);
        }
        
        .mentor-theme .user-badge {
            background: linear-gradient(135deg, rgba(63, 168, 69, 0.1), rgba(63, 168, 69, 0.15));
            color: var(--mentor-color);
            border: 2px solid rgba(63, 168, 69, 0.2);
        }
        
        .project-theme .user-badge {
            background: linear-gradient(135deg, rgba(14, 1, 91, 0.1), rgba(14, 1, 91, 0.15));
            color: var(--project-color);
            border: 2px solid rgba(14, 1, 91, 0.2);
        }
        
        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--deep-purple));
            border: none;
            color: var(--pure-white);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(44, 64, 154, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--deep-purple), var(--primary-blue));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(44, 64, 154, 0.4);
        }
        
        .btn-success {
            background: var(--success-green);
            border: none;
            color: var(--pure-white);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(63, 168, 69, 0.3);
        }
        
        .btn-success:hover {
            background: #359c3b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(63, 168, 69, 0.4);
        }
        
        .btn-danger {
            background: var(--alert-red);
            border: none;
            color: var(--pure-white);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(253, 22, 22, 0.3);
        }
        
        .btn-danger:hover {
            background: #e01414;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(253, 22, 22, 0.4);
        }
        
        .btn-warning {
            background: #ffc107;
            border: none;
            color: var(--pure-black);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-warning:hover {
            background: #ffb300;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: #17a2b8;
            border: none;
            color: var(--pure-white);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--deep-purple));
            color: var(--pure-white);
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 20px;
            font-weight: 600;
            border-bottom: none;
        }
        
        .card-header.bg-success {
            background: linear-gradient(135deg, var(--success-green), #2d8736) !important;
        }
        
        .card-header.bg-danger {
            background: linear-gradient(135deg, var(--alert-red), #d11212) !important;
        }
        
        .card-header.bg-info {
            background: linear-gradient(135deg, #17a2b8, #117a8b) !important;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Bordered Cards */
        .card.border-left-primary {
            border-left: 4px solid var(--primary-blue) !important;
        }
        
        .card.border-left-success {
            border-left: 4px solid var(--success-green) !important;
        }
        
        .card.border-left-danger {
            border-left: 4px solid var(--alert-red) !important;
        }
        
        .card.border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }
        
        .card.border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(63, 168, 69, 0.1), rgba(63, 168, 69, 0.15));
            color: var(--success-green);
            border-left: 4px solid var(--success-green);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(253, 22, 22, 0.1), rgba(253, 22, 22, 0.15));
            color: var(--alert-red);
            border-left: 4px solid var(--alert-red);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.15));
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.15));
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        /* ===== TABLES ===== */
        .table {
            background: var(--pure-white);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-blue), var(--deep-purple));
            color: var(--pure-white);
            font-weight: 600;
            border: none;
            padding: 14px 16px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(44, 64, 154, 0.05);
        }
        
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* ===== BADGES ===== */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.bg-primary {
            background: var(--primary-blue) !important;
        }
        
        .badge.bg-success {
            background: var(--success-green) !important;
        }
        
        .badge.bg-danger {
            background: var(--alert-red) !important;
        }
        
        /* ===== FORMS ===== */
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(44, 64, 154, 0.15);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        /* ===== MOBILE MENU TOGGLE ===== */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-dark);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .mobile-menu-toggle:hover {
            background: var(--bg-light);
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
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
            
            .sidebar-brand img {
                max-width: 160px;
                max-height: 55px;
            }
            
            .content-wrapper {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .top-navbar {
                padding: 12px 20px;
            }
            
            .top-navbar-right {
                gap: 10px;
            }
            
            .user-badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 15px;
            }
            
            .top-navbar {
                padding: 10px 15px;
            }
            
            .sidebar-brand img {
                max-width: 140px;
                max-height: 45px;
            }
            
            .sidebar-brand {
                padding: 12px 15px;
                min-height: 70px;
            }
            
            .card-body {
                padding: 16px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
        
        /* ===== UTILITY CLASSES ===== */
        .text-primary {
            color: var(--primary-blue) !important;
        }
        
        .text-success {
            color: var(--success-green) !important;
        }
        
        .text-danger {
            color: var(--alert-red) !important;
        }
        
        .bg-primary {
            background-color: var(--primary-blue) !important;
        }
        
        .bg-success {
            background-color: var(--success-green) !important;
        }
        
        .bg-danger {
            background-color: var(--alert-red) !important;
        }
        
        /* ===== LOADING SPINNER ===== */
        .spinner-border {
            border-color: var(--primary-blue);
            border-right-color: transparent;
        }
        
        /* ===== HOVER EFFECTS ===== */
        .hover-lift {
            transition: all 0.3s;
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
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
            
            <!-- Sidebar Toggle Button -->
            <div class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
                <i class="fas fa-chevron-left"></i>
            </div>
            
            <!-- Navigation -->
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
                        <?php if ($stats['pending_applications'] ?? 0 > 0): ?>
                            <span class="badge bg-danger"><?php echo $stats['pending_applications']; ?></span>
                        <?php endif; ?>
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
                    <?php if ($pendingCommentsCount > 0): ?>
                    <a href="<?php echo $baseUrl; ?>/dashboards/admin/moderate-comments.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>Comments</span>
                        <span class="badge bg-warning"><?php echo $pendingCommentsCount; ?></span>
                    </a>
                    <?php endif; ?>
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
                    <a href="<?php echo $baseUrl; ?>/dashboards/mentor/index.php#my-projects" class="nav-link">
                        <i class="fas fa-project-diagram"></i>
                        <span>My Projects</span>
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
                <hr>
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
        <div class="main-content" id="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="top-navbar-left">
                    <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="mb-0 fw-bold text-dark d-none d-md-block">
                        <?php echo isset($pageTitle) ? str_replace(' - ' . SITE_NAME, '', $pageTitle) : 'Dashboard'; ?>
                    </h5>
                </div>
                
                <div class="top-navbar-right">
                    <div class="user-badge">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($currentUserName); ?></span>
                        <span class="badge bg-light text-primary ms-1"><?php echo $userRole; ?></span>
                    </div>
                </div>
            </nav>
            
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <?php
                // Display flash messages if any
                if (isset($_SESSION['flash_message'])):
                    $flashType = $_SESSION['flash_type'] ?? 'info';
                ?>
                <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                endif;
                ?>