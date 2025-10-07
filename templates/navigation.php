<?php
// templates/navigation.php - Navigation Component
// This file provides a reusable navigation component that can be included separately if needed

// Get current user info
$currentUserType = $auth->getUserType() ?? null;
$baseUrl = SITE_URL ?? 'http://localhost/jhub-africa-tracker';

/**
 * Get navigation items based on user type
 */
function getNavigationItems($userType, $baseUrl) {
    $navItems = [];
    
    if ($userType === USER_TYPE_ADMIN) {
        $navItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => $baseUrl . '/dashboards/admin/index.php',
                'active' => false
            ],
            [
                'label' => 'Applications',
                'icon' => 'fas fa-clipboard-list',
                'url' => $baseUrl . '/dashboards/admin/applications.php',
                'badge' => null, // Can be populated with pending count
                'active' => false
            ],
            [
                'label' => 'Projects',
                'icon' => 'fas fa-project-diagram',
                'url' => $baseUrl . '/dashboards/admin/projects.php',
                'active' => false
            ],
            [
                'label' => 'Mentors',
                'icon' => 'fas fa-user-tie',
                'url' => $baseUrl . '/dashboards/admin/mentors.php',
                'active' => false
            ],
            [
                'label' => 'Add Mentor',
                'icon' => 'fas fa-user-plus',
                'url' => $baseUrl . '/dashboards/admin/register-mentor.php',
                'active' => false
            ],
            [
                'label' => 'Admins',
                'icon' => 'fas fa-users-cog',
                'url' => $baseUrl . '/dashboards/admin/admin-management.php',
                'active' => false
            ],
            [
                'label' => 'Reports',
                'icon' => 'fas fa-chart-bar',
                'url' => $baseUrl . '/dashboards/admin/reports.php',
                'active' => false
            ],
            [
                'label' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => $baseUrl . '/dashboards/admin/settings.php',
                'active' => false
            ]
        ];
    } elseif ($userType === USER_TYPE_MENTOR) {
        $navItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => $baseUrl . '/dashboards/mentor/index.php',
                'active' => false
            ],
            [
                'label' => 'Browse Projects',
                'icon' => 'fas fa-search',
                'url' => $baseUrl . '/dashboards/mentor/available-projects.php',
                'active' => false
            ],
            [
                'label' => 'My Projects',
                'icon' => 'fas fa-project-diagram',
                'url' => $baseUrl . '/dashboards/mentor/index.php#my-projects',
                'active' => false
            ],
            [
                'label' => 'Resources',
                'icon' => 'fas fa-folder',
                'url' => $baseUrl . '/dashboards/mentor/resources.php',
                'active' => false
            ],
            [
                'label' => 'Assessments',
                'icon' => 'fas fa-clipboard-check',
                'url' => $baseUrl . '/dashboards/mentor/assessments.php',
                'active' => false
            ],
            [
                'label' => 'Learning',
                'icon' => 'fas fa-graduation-cap',
                'url' => $baseUrl . '/dashboards/mentor/learning.php',
                'active' => false
            ],
            [
                'label' => 'My Profile',
                'icon' => 'fas fa-user',
                'url' => $baseUrl . '/dashboards/mentor/profile.php',
                'active' => false
            ]
        ];
    } elseif ($userType === USER_TYPE_PROJECT) {
        $navItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => $baseUrl . '/dashboards/project/index.php',
                'active' => false
            ],
            [
                'label' => 'Team',
                'icon' => 'fas fa-users',
                'url' => $baseUrl . '/dashboards/project/team.php',
                'active' => false
            ],
            [
                'label' => 'Resources',
                'icon' => 'fas fa-folder',
                'url' => $baseUrl . '/dashboards/project/resources.php',
                'active' => false
            ],
            [
                'label' => 'Assessments',
                'icon' => 'fas fa-clipboard-check',
                'url' => $baseUrl . '/dashboards/project/assessments.php',
                'active' => false
            ],
            [
                'label' => 'Learning',
                'icon' => 'fas fa-graduation-cap',
                'url' => $baseUrl . '/dashboards/project/learning.php',
                'active' => false
            ],
            [
                'label' => 'Progress',
                'icon' => 'fas fa-chart-line',
                'url' => $baseUrl . '/dashboards/project/progress.php',
                'active' => false
            ],
            [
                'label' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => $baseUrl . '/dashboards/project/profile.php',
                'active' => false
            ]
        ];
    }
    
    return $navItems;
}

/**
 * Render navigation items
 */
function renderNavigation($userType, $baseUrl, $currentPath = null) {
    $navItems = getNavigationItems($userType, $baseUrl);
    
    if (empty($navItems)) {
        return '';
    }
    
    // Get current path if not provided
    if ($currentPath === null) {
        $currentPath = $_SERVER['PHP_SELF'] ?? '';
    }
    
    $html = '<nav class="sidebar-nav">';
    
    foreach ($navItems as $item) {
        $isActive = false;
        $itemPath = parse_url($item['url'], PHP_URL_PATH);
        
        // Check if current page matches this nav item
        if ($itemPath && strpos($currentPath, $itemPath) !== false) {
            $isActive = true;
            $item['active'] = true;
        }
        
        $activeClass = $isActive ? ' active' : '';
        $badge = isset($item['badge']) && $item['badge'] ? 
                 '<span class="badge bg-danger rounded-pill ms-auto">' . $item['badge'] . '</span>' : '';
        
        $html .= sprintf(
            '<a href="%s" class="nav-link%s">
                <i class="%s"></i>
                <span>%s</span>
                %s
            </a>',
            htmlspecialchars($item['url']),
            $activeClass,
            htmlspecialchars($item['icon']),
            htmlspecialchars($item['label']),
            $badge
        );
    }
    
    // Add common links
    $html .= '
        <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
        <a href="' . $baseUrl . '/public/projects.php" class="nav-link" target="_blank">
            <i class="fas fa-globe"></i>
            <span>Public Projects</span>
        </a>
        <a href="' . $baseUrl . '/auth/logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    ';
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get breadcrumb trail
 */
function getBreadcrumb($currentPage = '') {
    $breadcrumbs = [];
    $path = $_SERVER['PHP_SELF'] ?? '';
    $pathParts = explode('/', trim($path, '/'));
    
    $baseUrl = SITE_URL ?? '';
    $currentUrl = $baseUrl;
    
    // Always start with Dashboard
    $breadcrumbs[] = [
        'label' => 'Dashboard',
        'url' => $baseUrl . '/dashboards/',
        'active' => false
    ];
    
    // Build breadcrumb trail from path
    foreach ($pathParts as $index => $part) {
        if (empty($part) || $part === 'index.php') continue;
        
        $currentUrl .= '/' . $part;
        $label = ucwords(str_replace(['-', '_', '.php'], ' ', $part));
        
        $isLast = ($index === count($pathParts) - 1);
        
        $breadcrumbs[] = [
            'label' => $label,
            'url' => $isLast ? null : $currentUrl,
            'active' => $isLast
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Render breadcrumb
 */
function renderBreadcrumb($currentPage = '') {
    $breadcrumbs = getBreadcrumb($currentPage);
    
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($breadcrumbs as $crumb) {
        if ($crumb['active'] || $crumb['url'] === null) {
            $html .= sprintf(
                '<li class="breadcrumb-item active" aria-current="page">%s</li>',
                htmlspecialchars($crumb['label'])
            );
        } else {
            $html .= sprintf(
                '<li class="breadcrumb-item"><a href="%s">%s</a></li>',
                htmlspecialchars($crumb['url']),
                htmlspecialchars($crumb['label'])
            );
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Get user menu items
 */
function getUserMenuItems($userType, $baseUrl) {
    $menuItems = [];
    
    if ($userType === USER_TYPE_ADMIN) {
        $menuItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => $baseUrl . '/dashboards/admin/index.php'
            ],
            [
                'label' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => $baseUrl . '/dashboards/admin/settings.php'
            ],
            [
                'divider' => true
            ],
            [
                'label' => 'Logout',
                'icon' => 'fas fa-sign-out-alt',
                'url' => $baseUrl . '/auth/logout.php',
                'class' => 'text-danger'
            ]
        ];
    } else {
        $menuItems = [
            [
                'label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => $baseUrl . '/dashboards/' . strtolower($userType) . '/index.php'
            ],
            [
                'label' => 'My Profile',
                'icon' => 'fas fa-user',
                'url' => $baseUrl . '/dashboards/' . strtolower($userType) . '/profile.php'
            ],
            [
                'divider' => true
            ],
            [
                'label' => 'Logout',
                'icon' => 'fas fa-sign-out-alt',
                'url' => $baseUrl . '/auth/logout.php',
                'class' => 'text-danger'
            ]
        ];
    }
    
    return $menuItems;
}

// Export functions for use in templates
// These can be called directly in other template files
?>