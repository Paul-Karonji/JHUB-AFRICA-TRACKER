<?php
// templates/header.php
// Common Header Template
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo getBaseUrl(); ?>/assets/css/main.css" rel="stylesheet">
    <?php if ($auth->getUserType() === USER_TYPE_ADMIN): ?>
    <link href="<?php echo getBaseUrl(); ?>/assets/css/admin.css" rel="stylesheet">
    <?php elseif ($auth->getUserType() === USER_TYPE_MENTOR): ?>
    <link href="<?php echo getBaseUrl(); ?>/assets/css/mentor.css" rel="stylesheet">
    <?php elseif ($auth->getUserType() === USER_TYPE_PROJECT): ?>
    <link href="<?php echo getBaseUrl(); ?>/assets/css/project.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
        <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <meta name="description" content="JHUB AFRICA Project Tracker - Nurturing African Innovation">
    <meta name="keywords" content="innovation, africa, startup, mentorship, incubator">
    <meta name="author" content="JHUB AFRICA">
    
    <link rel="icon" type="image/x-icon" href="<?php echo getBaseUrl(); ?>/favicon.ico">
</head>
<body class="<?php echo $auth->getUserType() ? 'dashboard-body' : 'public-body'; ?>">

    <?php if ($auth->isValidSession()): ?>
        <!-- Dashboard Navigation -->
        <?php include 'navigation.php'; ?>
        
        <div class="dashboard-wrapper">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <div class="container-fluid">
                    <!-- Flash Messages -->
                    <?php echo displayFlashMessages(); ?>
    <?php else: ?>
        <!-- Public Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>/">
                    <i class="fas fa-rocket me-2"></i>JHUB AFRICA
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/public/projects.php">Projects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/public/about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/applications/submit.php">Apply</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                Login
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/auth/admin-login.php">Admin</a></li>
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/auth/mentor-login.php">Mentor</a></li>
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/auth/project-login.php">Project</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <main class="public-content">
            <div class="container">
                <!-- Flash Messages -->
                <?php echo displayFlashMessages(); ?>
    <?php endif; ?>