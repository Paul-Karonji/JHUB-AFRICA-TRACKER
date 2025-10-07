<?php
// classes/EmailService.php
// Complete Email Service with PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $database;
    
    public function __construct() {
        $this->database = Database::getInstance();
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer with configuration
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Get email settings from database or config
            $smtpHost = $this->getSetting('smtp_host', SMTP_HOST);
            $smtpPort = $this->getSetting('smtp_port', SMTP_PORT);
            $smtpUsername = $this->getSetting('smtp_username', SMTP_USERNAME);
            $smtpPassword = $this->getSetting('smtp_password', SMTP_PASSWORD);
            $smtpEncryption = $this->getSetting('smtp_encryption', SMTP_ENCRYPTION);
            $fromEmail = $this->getSetting('smtp_from_email', SMTP_FROM_EMAIL);
            $fromName = $this->getSetting('smtp_from_name', SMTP_FROM_NAME);
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtpHost;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $smtpUsername;
            $this->mailer->Password = $smtpPassword;
            $this->mailer->SMTPSecure = $smtpEncryption;
            $this->mailer->Port = $smtpPort;
            
            // Sender information
            $this->mailer->setFrom($fromEmail, $fromName);
            
            // Character encoding
            $this->mailer->CharSet = 'UTF-8';
            
            // Debug mode (from settings)
            if (EMAIL_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
        } catch (Exception $e) {
            error_log("Email initialization error: " . $e->getMessage());
            throw new Exception("Failed to initialize email service");
        }
    }
    
    /**
     * Send email notification
     */
    public function sendEmail($to, $subject, $body, $type = 'general', $attachments = []) {
        // Check if emails are enabled
        if (!$this->getSetting('email_enabled', EMAIL_ENABLED)) {
            error_log("Email disabled: Would send to {$to} - Subject: {$subject}");
            return $this->queueEmail($to, $subject, $body, $type);
        }
        
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($to);
            
            // Add attachments if any
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $this->mailer->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? basename($attachment['path'])
                    );
                }
            }
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body); // Plain text version
            
            // Send email
            $this->mailer->send();
            
            // Log successful send
            $notificationId = $this->queueEmail($to, $subject, $body, $type, 'sent');
            
            error_log("Email sent successfully to: {$to}");
            return $notificationId;
            
        } catch (Exception $e) {
            error_log("Email send error: {$this->mailer->ErrorInfo}");
            
            // Queue for retry
            $notificationId = $this->queueEmail($to, $subject, $body, $type, 'failed');
            
            // Update with error message
            $this->database->update(
                'email_notifications',
                ['error_message' => $this->mailer->ErrorInfo],
                'notification_id = ?',
                [$notificationId]
            );
            
            return false;
        }
    }
    
    /**
     * Queue email for sending/logging
     */
    private function queueEmail($to, $subject, $body, $type, $status = 'pending') {
        return $this->database->insert('email_notifications', [
            'recipient_email' => $to,
            'subject' => $subject,
            'message_body' => $body,
            'notification_type' => $type,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Send email using template
     */
    public function sendTemplateEmail($to, $type, $data = []) {
        $template = $this->getEmailTemplate($type);
        
        if (!$template) {
            error_log("Email template not found: {$type}");
            return false;
        }
        
        // Load template
        $templatePath = EMAIL_TEMPLATES_DIR . $template['template'];
        
        if (!file_exists($templatePath)) {
            error_log("Email template file not found: {$templatePath}");
            return false;
        }
        
        // Read template
        $body = file_get_contents($templatePath);
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        
        // Default replacements
        $body = str_replace('{{site_name}}', SITE_NAME, $body);
        $body = str_replace('{{site_url}}', SITE_URL, $body);
        $body = str_replace('{{current_year}}', date('Y'), $body);
        
        return $this->sendEmail($to, $template['subject'], $body, $type);
    }
    
    /**
     * Get email template configuration
     */
    private function getEmailTemplate($type) {
        $templates = [
            'application_submitted' => [
                'template' => 'application-submitted.html',
                'subject' => 'Application Submitted - JHUB AFRICA'
            ],
            'application_approved' => [
                'template' => 'application-approved.html',
                'subject' => 'Your Application Has Been Approved!'
            ],
            'application_rejected' => [
                'template' => 'application-rejected.html',
                'subject' => 'Update on Your Application'
            ],
            'mentor_assigned' => [
                'template' => 'mentor-assigned.html',
                'subject' => 'New Mentor Assigned to Your Project'
            ],
            'stage_updated' => [
                'template' => 'stage-updated.html',
                'subject' => 'Your Project Has Advanced!'
            ],
            'system_alert' => [
                'template' => 'system-alert.html',
                'subject' => 'JHUB AFRICA System Notification'
            ]
        ];
        
        return $templates[$type] ?? null;
    }
    
    /**
     * Get setting from database or use default
     */
    private function getSetting($key, $default = null) {
        $setting = $this->database->getRow(
            "SELECT setting_value FROM system_settings WHERE setting_key = ?",
            [$key]
        );
        
        return $setting ? $setting['setting_value'] : $default;
    }
    
    /**
     * Test email configuration
     */
    public function testConfiguration($testEmail) {
        $subject = 'Test Email - JHUB AFRICA System';
        $message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Test Email Successful!</h2>
                <p>If you received this email, your SMTP configuration is working correctly.</p>
                <p><strong>Test Details:</strong></p>
                <ul>
                    <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
                    <li>System: " . SITE_NAME . "</li>
                    <li>Configuration Test: PASSED ✓</li>
                </ul>
                <p>This is an automated test message. Please do not reply.</p>
            </body>
            </html>
        ";
        
        return $this->sendEmail($testEmail, $subject, $message, 'system_test');
    }
    
    /**
     * Process pending email queue
     */
    public function processPendingEmails($limit = 10) {
        $pendingEmails = $this->database->getAll(
            "SELECT * FROM email_notifications 
             WHERE status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );
        
        $sent = 0;
        $failed = 0;
        
        foreach ($pendingEmails as $email) {
            // Update status to processing
            $this->database->update(
                'email_notifications',
                ['status' => 'processing'],
                'notification_id = ?',
                [$email['notification_id']]
            );
            
            // Try to send
            $result = $this->sendEmail(
                $email['recipient_email'],
                $email['subject'],
                $email['message_body'],
                $email['notification_type']
            );
            
            if ($result) {
                $sent++;
                $this->database->update(
                    'email_notifications',
                    [
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s')
                    ],
                    'notification_id = ?',
                    [$email['notification_id']]
                );
            } else {
                $failed++;
                // Increment retry count (using 'attempts' column)
                $retries = ($email['attempts'] ?? 0) + 1;
                $this->database->update(
                    'email_notifications',
                    [
                        'status' => $retries >= 3 ? 'failed' : 'pending',
                        'attempts' => $retries
                    ],
                    'notification_id = ?',
                    [$email['notification_id']]
                );
            }
        }
        
        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($pendingEmails)
        ];
    }
}
?>