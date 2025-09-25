<?php
// templates/navigation.php
// Dashboard Navigation Bar
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark dashboard-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>/">
            <i class="fas fa-rocket me-2"></i>JHUB AFRICA
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="dashboardNav">
            <ul class="navbar-nav me-auto">
                <?php if ($auth->getUserType() === USER_TYPE_ADMIN): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/admin/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/admin/applications.php">
                        <i class="fas fa-clipboard-list me-1"></i>Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/admin/projects.php">
                        <i class="fas fa-project-diagram me-1"></i>Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/admin/mentors.php">
                        <i class="fas fa-users me-1"></i>Mentors
                    </a>
                </li>
                
                <?php elseif ($auth->getUserType() === USER_TYPE_MENTOR): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/mentor/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/mentor/my-projects.php">
                        <i class="fas fa-project-diagram me-1"></i>My Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/mentor/available-projects.php">
                        <i class="fas fa-plus me-1"></i>Available Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/mentor/resources.php">
                        <i class="fas fa-share-alt me-1"></i>Resources
                    </a>
                </li>
                
                <?php elseif ($auth->getUserType() === USER_TYPE_PROJECT): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/project/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/project/team.php">
                        <i class="fas fa-users me-1"></i>Team
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/project/progress.php">
                        <i class="fas fa-chart-line me-1"></i>Progress
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/dashboards/project/resources.php">
                        <i class="fas fa-share-alt me-1"></i>Resources
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>/public/projects.php" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Public View
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo e($auth->getUserIdentifier()); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($auth->getUserType() === USER_TYPE_ADMIN): ?>
                        <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/dashboards/admin/settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/dashboards/admin/admin-management.php">
                            <i class="fas fa-users-cog me-2"></i>Admin Management
                        </a></li>
                        <?php elseif ($auth->getUserType() === USER_TYPE_MENTOR): ?>
                        <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/dashboards/mentor/profile.php">
                            <i class="fas fa-user me-2"></i>Profile
                        </a></li>
                        <?php elseif ($auth->getUserType() === USER_TYPE_PROJECT): ?>
                        <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/dashboards/project/settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>