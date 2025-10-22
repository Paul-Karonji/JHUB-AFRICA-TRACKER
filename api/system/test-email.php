<?php
// api/system/test-email.php - Send Test Email
header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin authentication
if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }

    // Validate CSRF token
    if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    // Validate email
    $testEmail = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$testEmail) {
        throw new Exception('Invalid email address');
    }

    // Load email settings from database
    function getEmailSetting($key, $default = '') {
        global $database;
        $setting = $database->getRow("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        return $setting ? $setting['setting_value'] : $default;
    }

    $emailEnabled = getEmailSetting('email_enabled', 0);
    if (!$emailEnabled) {
        throw new Exception('Email notifications are disabled. Please enable them in Email Settings first.');
    }

    // Get SMTP settings
    $smtpHost = getEmailSetting('smtp_host', 'smtp.gmail.com');
    $smtpPort = intval(getEmailSetting('smtp_port', 587));
    $smtpUsername = getEmailSetting('smtp_username', '');
    $smtpPassword = getEmailSetting('smtp_password', '');
    $smtpEncryption = getEmailSetting('smtp_encryption', 'tls');
    $fromEmail = getEmailSetting('smtp_from_email', 'noreply@jhubafrica.com');
    $fromName = getEmailSetting('smtp_from_name', 'JHUB AFRICA');

    // Validate required settings
    if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
        throw new Exception('SMTP settings are incomplete. Please configure all required fields.');
    }

    // Import PHPMailer classes
    require_once '../../vendor/PHPMailer/PHPMailer.php';
    require_once '../../vendor/PHPMailer/SMTP.php';
    require_once '../../vendor/PHPMailer/Exception.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    // Enable verbose debug output for testing
    $mail->SMTPDebug = 0; // Set to 2 for detailed debugging if needed
    $mail->Debugoutput = 'error_log';

    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = $smtpEncryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';

    // Recipients
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($testEmail);
    $mail->addReplyTo($fromEmail, $fromName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from ' . SITE_NAME;
    
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b54c7; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .info-box { background: white; padding: 15px; border-left: 4px solid #3b54c7; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ Test Email Successful</h1>
            </div>
            <div class="content">
                <div class="success">
                    <strong>Congratulations!</strong> Your email configuration is working correctly.
                </div>
                
                <p>This is a test email from your <strong>' . SITE_NAME . '</strong> system.</p>
                
                <div class="info-box">
                    <strong>SMTP Configuration Details:</strong><br>
                    • Host: ' . htmlspecialchars($smtpHost) . '<br>
                    • Port: ' . $smtpPort . '<br>
                    • Encryption: ' . strtoupper($smtpEncryption) . '<br>
                    • From: ' . htmlspecialchars($fromName) . ' &lt;' . htmlspecialchars($fromEmail) . '&gt;
                </div>
                
                <p>If you received this email, your system is ready to send:</p>
                <ul>
                    <li>Application notifications</li>
                    <li>Mentor assignment alerts</li>
                    <li>Project updates</li>
                    <li>System notifications</li>
                </ul>
                
                <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <strong>Test Time:</strong> ' . date('F j, Y \a\t g:i A') . '<br>
                    <strong>Server:</strong> ' . gethostname() . '
                </p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $mail->AltBody = 'Test Email - Your email configuration is working correctly! SMTP: ' . $smtpHost . ':' . $smtpPort;

    // Send email
    if (!$mail->send()) {
        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
    }

    // Log the test
    $adminId = $auth->getUserId();
    logActivity(
        'admin',
        $adminId,
        'test_email_sent',
        "Sent test email to {$testEmail}",
        null,
        ['recipient' => $testEmail, 'smtp_host' => $smtpHost]
    );

    echo json_encode([
        'success' => true,
        'message' => "Test email sent successfully to {$testEmail}",
        'smtp_info' => [
            'host' => $smtpHost,
            'port' => $smtpPort,
            'encryption' => $smtpEncryption
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>