<?php
/**
 * Email Configuration for JHUB AFRICA Project Tracker
 * Location: config/email.php
 */

// SMTP Configuration
define('SMTP_ENABLED', false); // Set to false for testing, true when SMTP is configured
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_SECURITY', 'tls'); // tls, ssl, or none
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_TIMEOUT', 30);

// Email Addresses
define('FROM_EMAIL', 'noreply@jhubafrica.com');
define('FROM_NAME', 'JHUB AFRICA');
define('REPLY_TO_EMAIL', 'support@jhubafrica.com');
define('ADMIN_EMAIL', 'admin@jhubafrica.com');

// Email Settings
define('EMAIL_QUEUE_ENABLED', true);
define('EMAIL_DEBUG', DEBUG_MODE);
define('EMAIL_CHARSET', 'UTF-8');
define('MAX_EMAIL_ATTEMPTS', 3);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/email/');

// Email Notification Types
define('NOTIFY_APPLICATION_RECEIVED', 'application_received');
define('NOTIFY_APPLICATION_APPROVED', 'application_approved');
define('NOTIFY_APPLICATION_REJECTED', 'application_rejected');
define('NOTIFY_PROJECT_CREATED', 'project_created');
define('NOTIFY_MENTOR_ASSIGNED', 'mentor_assigned');
define('NOTIFY_STAGE_COMPLETED', 'stage_completed');

?>