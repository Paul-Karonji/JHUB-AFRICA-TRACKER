<?php
/**
 * Email System Test Script
 * Usage: php test-email.php your-email@example.com
 */

require __DIR__ . '/vendor/autoload.php';

// Your existing include
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/init.php';

echo "===========================================\n";
echo "JHUB AFRICA - Email System Test\n";
echo "===========================================\n\n";

// Get test email from command line or use default
$testEmail = 'test@example.com'; // Default email

if (isset($argv[1])) {
    $testEmail = $argv[1];
}

if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Invalid email address: {$testEmail}\n";
    echo "Usage: php test-email.php your-email@example.com\n";
    exit(1);
}

echo "Test Email Address: {$testEmail}\n";
echo "-------------------------------------------\n\n";

// Test 1: Check PHPMailer Installation
echo "TEST 1: PHPMailer Installation\n";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✓ PHPMailer is installed\n";
} else {
    echo "✗ PHPMailer NOT found!\n";
    echo "  Run: composer require phpmailer/phpmailer\n";
    exit(1);
}
echo "\n";

// Test 2: Check Email Configuration
echo "TEST 2: Email Configuration\n";
echo "  SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED') . "\n";
echo "  SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED') . "\n";
echo "  From Email: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT DEFINED') . "\n";
echo "  Email Enabled: " . (defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'YES' : 'NO') : 'NOT DEFINED') . "\n";
echo "\n";

// Test 3: Check Email Templates Directory
echo "TEST 3: Email Templates\n";
$templatesDir = defined('EMAIL_TEMPLATES_DIR') ? EMAIL_TEMPLATES_DIR : __DIR__ . '/templates/emails/';
echo "  Templates Directory: {$templatesDir}\n";

if (is_dir($templatesDir)) {
    echo "✓ Templates directory exists\n";
    
    $templates = [
        'application-submitted.html',
        'application-approved.html',
        'application-rejected.html',
        'mentor-assigned.html',
        'stage-updated.html',
        'system-alert.html'
    ];
    
    foreach ($templates as $template) {
        $path = $templatesDir . $template;
        if (file_exists($path)) {
            echo "  ✓ {$template}\n";
        } else {
            echo "  ✗ {$template} - MISSING\n";
        }
    }
} else {
    echo "✗ Templates directory does not exist!\n";
    echo "  Create it: mkdir {$templatesDir}\n";
}
echo "\n";

// Test 4: Database Connection
echo "TEST 4: Database Connection\n";
try {
    $database = Database::getInstance();
    $result = $database->getRow("SELECT COUNT(*) as count FROM email_notifications");
    echo "✓ Database connection successful\n";
    echo "  Total emails in queue: {$result['count']}\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Send Simple Test Email
echo "TEST 5: Simple Email Test\n";
try {
    $subject = "JHUB Test Email - " . date('Y-m-d H:i:s');
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2>✓ Email System Test Successful!</h2>
            <p>This is a test email from JHUB AFRICA Project Tracker.</p>
            <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>System:</strong> " . (defined('SITE_NAME') ? SITE_NAME : 'JHUB AFRICA') . "</p>
            <hr>
            <p style='color: #28a745;'><strong>If you received this email, your basic email setup is working correctly!</strong></p>
        </body>
        </html>
    ";
    
    $result = sendEmailNotification($testEmail, $subject, $message, 'system_test');
    
    if ($result) {
        echo "✓ Simple email queued (ID: {$result})\n";
    } else {
        echo "✗ Failed to queue simple email\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check EmailService Class
echo "TEST 6: EmailService Class\n";
try {
    if (class_exists('EmailService')) {
        echo "✓ EmailService class found\n";
        $emailService = new EmailService();
        echo "✓ EmailService initialized successfully\n";
    } else {
        echo "✗ EmailService class not found\n";
        echo "  Make sure EmailService.php is in classes/ directory\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Check Pending Emails
echo "TEST 7: Pending Email Queue\n";
try {
    $pending = $database->getAll(
        "SELECT * FROM email_notifications WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5"
    );
    
    echo "  Pending emails: " . count($pending) . "\n";
    
    if (count($pending) > 0) {
        echo "\n  Recent pending emails:\n";
        foreach ($pending as $email) {
            echo "    - ID: {$email['notification_id']}, To: {$email['recipient_email']}, Type: {$email['notification_type']}\n";
        }
        
        echo "\n  ⚠ To process these emails, run:\n";
        echo "    php cron/process-emails.php\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n";
echo "✓ Tests completed!\n";
echo "✉ Test email sent to: {$testEmail}\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Check your inbox at: {$testEmail}\n";
echo "2. Also check your SPAM folder!\n";
echo "3. Process email queue: php cron/process-emails.php\n";
echo "4. Check database: SELECT * FROM email_notifications;\n";
echo "\n";
echo "===========================================\n";
?>