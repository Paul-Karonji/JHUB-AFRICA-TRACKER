<?php
// cron/process-emails.php
// Email Queue Processor - Run this via cron job every 5 minutes

// Prevent direct browser access
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die('Access denied');
}

// ✅ Add this line to load PHPMailer and other Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load application
require_once __DIR__ . '/../includes/init.php';

echo "===========================================\n";
echo "Email Queue Processor Started\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Initialize email service
    $emailService = new EmailService();
    
    // Process pending emails (batch of 20)
    echo "Processing pending emails...\n";
    $result = $emailService->processPendingEmails(20);
    
    echo "\nResults:\n";
    echo "  - Total processed: {$result['total']}\n";
    echo "  - Successfully sent: {$result['sent']}\n";
    echo "  - Failed: {$result['failed']}\n";
    
    // Log the processing
    if ($result['total'] > 0) {
        $database = Database::getInstance();
        $database->insert('activity_logs', [
            'user_type' => 'system',
            'user_id' => 0,
            'action' => 'email_queue_processed',
            'description' => "Processed {$result['total']} emails: {$result['sent']} sent, {$result['failed']} failed",
            'additional_data' => json_encode($result)
        ]);
    }
    
    echo "\n===========================================\n";
    echo "Email Queue Processor Completed\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    error_log("Email queue processor error: " . $e->getMessage());
}
?>
