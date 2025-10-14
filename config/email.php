<?php
// config/email.php
// Email Configuration

// SMTP Configuration (for production)
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'info.jhub@jkuat.ac.ke'); // Your email
define('SMTP_PASSWORD', ''); // Your email password or app password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', 'info.jhub@jkuat.ac.ke');
define('SMTP_FROM_NAME', 'JHUB AFRICA');

// Email Settings
define('EMAIL_ENABLED', true); // Set to false to disable emails (for testing)
define('EMAIL_DEBUG', false); // Set to true to see SMTP debug output

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/emails/');

// Admin Notification Emails
define('ADMIN_NOTIFICATION_EMAIL', 'admin@jhubafrica.com');

// Email Queue (for later implementation)
define('USE_EMAIL_QUEUE', false); // Set to true to queue emails instead of sending immediately

// Notification Types
define('NOTIFY_APPLICATION_SUBMITTED', 'application_submitted');
define('NOTIFY_APPLICATION_APPROVED', 'application_approved');
define('NOTIFY_APPLICATION_REJECTED', 'application_rejected');
define('NOTIFY_MENTOR_ASSIGNED', 'mentor_assigned');
define('NOTIFY_RESOURCE_SHARED', 'resource_shared');
define('NOTIFY_ASSESSMENT_CREATED', 'assessment_created');
define('NOTIFY_STAGE_UPDATED', 'stage_updated');
define('NOTIFY_COMMENT_POSTED', 'comment_posted');

?>