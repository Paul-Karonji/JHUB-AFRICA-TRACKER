# .htaccess - JHUB AFRICA Project Tracker
# Apache Configuration and URL Rewriting

# Enable RewriteEngine
RewriteEngine On

# Set base directory (change this if your app is in a subdirectory)
# RewriteBase /jhub-africa-tracker

# ========================================
# SECURITY MEASURES
# ========================================

# Prevent access to sensitive files
<FilesMatch "\.(ini|log|conf|sql|md|txt|yml|yaml)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect configuration and class files
<FilesMatch "\.(php)$">
    <IfModule mod_rewrite.c>
        RewriteCond %{REQUEST_URI} ^.*(config|classes|includes|logs|database|tests|vendor)/.*$
        RewriteCond %{REQUEST_URI} !^.*(index\.php)$
        RewriteRule ^(.*)$ - [F,L]
    </IfModule>
</FilesMatch>

# Prevent access to hidden files (.htaccess, .git, etc.)
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory browsing
Options -Indexes

# Protect uploads directory from PHP execution
<Directory "assets/uploads">
    php_flag engine off
    AddType text/plain .php .php3 .phtml .pht .pl .py .cgi .sh
</Directory>

# ========================================
# PERFORMANCE OPTIMIZATIONS
# ========================================

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/ico "access plus 1 month"
    ExpiresByType image/icon "access plus 1 month"
    ExpiresByType text/x-component "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
</IfModule>

# Set ETags
<IfModule mod_headers.c>
    Header unset ETag
    FileETag None
    
    # Set proper MIME types
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options DENY
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    
    # CORS headers for API
    <FilesMatch "\.(json|xml)$">
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
        Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    </FilesMatch>
</IfModule>

# ========================================
# ERROR HANDLING
# ========================================

# Custom error pages
ErrorDocument 400 /error-pages/400.html
ErrorDocument 401 /error-pages/401.html
ErrorDocument 403 /error-pages/403.html
ErrorDocument 404 /error-pages/404.html
ErrorDocument 500 /error-pages/500.html

# ========================================
# URL REWRITING RULES
# ========================================

# Remove trailing slash from directories
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{THE_REQUEST} \s/+(.+?)//+[\s?] [NC]
RewriteRule ^ /%1/ [R=301,L]

# Add trailing slash to directories
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^(.+)$ $1/ [R=301,L]

# Redirect common old URLs to new structure (if migrating from old system)
# RewriteRule ^old-admin/?$ /dashboards/admin/ [R=301,L]
# RewriteRule ^old-projects/?$ /public/projects.php [R=301,L]

# API routing - route all /api requests to api/index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php?route=$1 [QSA,L]

# Clean URLs for public pages
RewriteRule ^projects/?$ public/projects.php [L]
RewriteRule ^project/([0-9]+)/?$ public/project-details.php?id=$1 [L]
RewriteRule ^about/?$ public/about.php [L]
RewriteRule ^contact/?$ public/contact.php [L]
RewriteRule ^apply/?$ applications/submit.php [L]

# Dashboard routing
RewriteRule ^dashboard/?$ auth/login.php [L]
RewriteRule ^admin/?$ dashboards/admin/index.php [L]
RewriteRule ^mentor/?$ dashboards/mentor/index.php [L]

# Login routing
RewriteRule ^login/?$ auth/login.php [L]
RewriteRule ^login/admin/?$ auth/admin-login.php [L]
RewriteRule ^login/mentor/?$ auth/mentor-login.php [L]
RewriteRule ^login/project/?$ auth/project-login.php [L]
RewriteRule ^logout/?$ auth/logout.php [L]

# ========================================
# PHP CONFIGURATION
# ========================================

# Set PHP configuration
<IfModule mod_php.c>
    # Security settings
    php_flag expose_php off
    php_flag allow_url_fopen off
    php_flag allow_url_include off
    
    # Error reporting (adjust for production)
    php_flag display_errors off
    php_flag log_errors on
    php_value error_log "logs/php_errors.log"
    
    # Session security
    php_value session.cookie_httponly 1
    php_value session.cookie_secure 1
    php_value session.use_only_cookies 1
    php_value session.entropy_file "/dev/urandom"
    php_value session.entropy_length 32
    
    # Upload settings
    php_value upload_max_filesize "10M"
    php_value post_max_size "12M"
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit "256M"
    
    # Default timezone
    php_value date.timezone "Africa/Nairobi"
</IfModule>

# ========================================
# FORCE HTTPS (uncomment for production)
# ========================================

# RewriteCond %{HTTPS} !=on
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# ========================================
# MAINTENANCE MODE (uncomment when needed)
# ========================================

# RewriteCond %{REQUEST_URI} !/maintenance.html$
# RewriteCond %{REMOTE_ADDR} !^192\.168\.1\.1$  # Replace with your IP
# RewriteRule $ /maintenance.html [R=302,L]

# robots.txt - SEO Configuration
User-agent: *

# Allow access to public pages
Allow: /
Allow: /public/
Allow: /assets/css/
Allow: /assets/js/
Allow: /assets/images/

# Disallow access to sensitive areas
Disallow: /config/
Disallow: /classes/
Disallow: /includes/
Disallow: /api/
Disallow: /dashboards/
Disallow: /auth/
Disallow: /logs/
Disallow: /database/
Disallow: /tests/
Disallow: /vendor/
Disallow: /applications/
Disallow: /assets/uploads/

# Allow access to specific public files
Allow: /robots.txt
Allow: /sitemap.xml
Allow: /favicon.ico

# Crawl delay (optional)
Crawl-delay: 1

# Sitemap location
Sitemap: https://yourdomain.com/sitemap.xml

# Additional files for Week 2-3 Foundation

# includes/session.php - Session Management
<?php
/**
 * Session Management Functions
 * Enhanced session security and management
 */

// Ensure this file is included via init.php
if (!defined('SITE_NAME')) {
    die('Direct access not permitted');
}

/**
 * Start secure session with enhanced security
 */
function startSecureSession() {
    // Set session configuration before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session name
    session_name(SESSION_NAME);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically for security
    regenerateSessionId();
    
    // Initialize session security
    initializeSessionSecurity();
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerateSessionId() {
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Initialize session security measures
 */
function initializeSessionSecurity() {
    // Store user agent and IP for validation
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    if (!isset($_SESSION['ip_address'])) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Validate session integrity
    validateSession();
}

/**
 * Validate session integrity
 */
function validateSession() {
    $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Check for session hijacking attempts
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
        destroySession();
        header('Location: /auth/login.php?error=session_invalid');
        exit;
    }
    
    // Optional: Check IP address (uncomment if needed, but may cause issues with mobile users)
    // if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIp) {
    //     destroySession();
    //     header('Location: /auth/login.php?error=session_invalid');
    //     exit;
    // }
}

/**
 * Destroy session securely
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
}

/**
 * Check if session is expired
 */
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    $inactive = time() - $_SESSION['last_activity'];
    
    if ($inactive >= SESSION_TIMEOUT) {
        return true;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Set session timeout warning
 */
function getSessionTimeoutWarning() {
    if (!isset($_SESSION['last_activity'])) {
        return null;
    }
    
    $timeLeft = SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
    
    // Warning when 5 minutes left
    if ($timeLeft <= 300 && $timeLeft > 0) {
        return $timeLeft;
    }
    
    return null;
}

/**
 * Extend session
 */
function extendSession() {
    $_SESSION['last_activity'] = time();
    return true;
}

# config/email.php - Email Configuration
<?php
/**
 * Email Configuration for JHUB AFRICA Project Tracker
 * SMTP settings and email templates configuration
 */

// SMTP Configuration
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'localhost'); // Change to your SMTP server
define('SMTP_PORT', 587);
define('SMTP_SECURITY', 'tls'); // tls, ssl, or none
define('SMTP_USERNAME', ''); // SMTP username
define('SMTP_PASSWORD', ''); // SMTP password
define('SMTP_TIMEOUT', 30);

// Email Addresses
define('FROM_EMAIL', 'noreply@jhubafrica.com');
define('FROM_NAME', 'JHUB AFRICA');
define('REPLY_TO_EMAIL', 'support@jhubafrica.com');
define('ADMIN_EMAIL', 'admin@jhubafrica.com');

// Email Settings
define('EMAIL_QUEUE_ENABLED', false); // Enable email queue for better performance
define('EMAIL_DEBUG', DEBUG_MODE);
define('EMAIL_CHARSET', 'UTF-8');
define('MAX_EMAIL_ATTEMPTS', 3);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/email/');

// Email Types and Templates
$EMAIL_TEMPLATES = [
    NOTIFY_APPLICATION_APPROVED => [
        'template' => 'application-approved.html',
        'subject' => 'Your JHUB AFRICA Application Has Been Approved!'
    ],
    NOTIFY_APPLICATION_REJECTED => [
        'template' => 'application-rejected.html',
        'subject' => 'Update on Your JHUB AFRICA Application'
    ],
    NOTIFY_MENTOR_ASSIGNED => [
        'template' => 'mentor-assignment.html',
        'subject' => 'New Mentor Assigned to Your Project'
    ],
    NOTIFY_STAGE_UPDATED => [
        'template' => 'stage-progression.html',
        'subject' => 'Your Project Has Advanced to the Next Stage!'
    ],
    NOTIFY_SYSTEM_ALERT => [
        'template' => 'system-alert.html',
        'subject' => 'JHUB AFRICA System Notification'
    ]
];

/**
 * Get email template configuration
 */
function getEmailTemplate($type) {
    global $EMAIL_TEMPLATES;
    return isset($EMAIL_TEMPLATES[$type]) ? $EMAIL_TEMPLATES[$type] : null;
}

/**
 * Build email template path
 */
function getEmailTemplatePath($template) {
    return EMAIL_TEMPLATES_DIR . $template;
}

# error-pages/404.html - Custom Error Page
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - JHUB AFRICA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .btn-home {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem;"></i>
            <h2>Page Not Found</h2>
            <p>The page you're looking for doesn't exist or has been moved.</p>
        </div>
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
        <div class="mt-4">
            <small>
                <i class="fas fa-envelope me-1"></i>
                Need help? Contact us at support@jhubafrica.com
            </small>
        </div>
    </div>
</body>
</html>

# maintenance.html - Maintenance Page Template
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - JHUB AFRICA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .maintenance-container {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 2rem;
        }
        .maintenance-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            opacity: 0.8;
        }
        .maintenance-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .maintenance-message {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            line-height: 1.6;
        }
        .spinner {
            margin: 2rem 0;
        }
        .contact-info {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        <div class="maintenance-title">Under Maintenance</div>
        <div class="maintenance-message">
            <p>We're currently performing some maintenance on our system to bring you an even better experience.</p>
            <p>We'll be back online shortly. Thank you for your patience!</p>
        </div>
        
        <div class="spinner">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        
        <div class="contact-info">
            <h5><i class="fas fa-envelope me-2"></i>Need Immediate Assistance?</h5>
            <p>Contact us at: <strong>support@jhubafrica.com</strong></p>
            <small>Expected downtime: 2-4 hours</small>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 minutes to check if maintenance is complete
        setTimeout(function(){
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>

# favicon.ico placeholder (you'll need to replace this with actual favicon)
# For now, this is just a comment indicating where the favicon should go

# README.md - Project Documentation
# JHUB AFRICA Project Tracker

A comprehensive web-based innovation management system designed to track and support innovation projects through a structured 6-stage development journey.

## Week 2-3 Foundation Complete ✅

This foundation includes:

### Core Architecture
- ✅ Database schema (`tracker` database with all necessary tables)
- ✅ Configuration files (database, app, constants, email)
- ✅ Core PHP classes (Database, Auth, Validator)
- ✅ Helper functions and initialization system
- ✅ Session management with security features

### User Interface
- ✅ Responsive landing page
- ✅ Comprehensive dashboards for all user types (Admin, Mentor, Project)
- ✅ Complete authentication system (multi-role login)
- ✅ Template system with reusable components
- ✅ Mobile-responsive design with Bootstrap 5

### Frontend Assets
- ✅ Main CSS with role-specific styling
- ✅ Authentication page styling
- ✅ JavaScript functionality (forms, validation, AJAX)
- ✅ File upload handling with drag-and-drop

### Security & Performance
- ✅ .htaccess with security measures and URL rewriting
- ✅ CSRF protection
- ✅ Session security and management
- ✅ Input validation and sanitization
- ✅ File upload security
- ✅ Error handling and custom error pages

## Quick Setup (XAMPP)

1. **Database Setup:**
   - Start XAMPP (Apache + MySQL)
   - Open phpMyAdmin
   - Create new database or import the SQL file provided
   - Run the database schema to create the `tracker` database

2. **File Setup:**
   - Extract project files to `htdocs/jhub-africa-tracker/`
   - Ensure proper file permissions for uploads directory

3. **Configuration:**
   - Verify database connection settings in `config/database.php`
   - Update base URL in `config/app.php` if needed

4. **Test Access:**
   - Visit `http://localhost/jhub-africa-tracker/`
   - Try logging in with admin credentials: `admin` / `admin123`

## Default Login Credentials

- **Admin**: Username: `admin`, Password: `admin123`
- **Mentors**: Created by admin (sample mentors included in database)
- **Projects**: Created through application process or by admin

## Next Steps (Week 4+)

- Implement email notification system
- Build project application submission
- Create admin application review interface
- Add mentor resource management
- Implement assessment and learning systems
- Add reporting and analytics

## Support

For development questions or issues, refer to the documentation in the `/docs` folder or contact the development team.

---

**Status**: Foundation Complete ✅  
**Version**: 1.0.0-foundation  
**Last Updated**: Week 2-3 Foundation