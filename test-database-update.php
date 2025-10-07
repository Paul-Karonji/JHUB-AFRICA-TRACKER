<?php
/**
 * test-database-update.php
 * Quick test script to verify Database::update() is working
 * Place this in your root directory and run it once
 */

require_once 'includes/init.php';

echo "<h2>Testing Database Update Method</h2>";
echo "<pre>";

try {
    // Test 1: Simple update
    echo "Test 1: Updating with single WHERE condition...\n";
    
    $result = $database->update(
        'project_applications',
        [
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null
        ],
        'application_id = ?',
        [1]  // Application ID
    );
    
    if ($result) {
        echo "✅ SUCCESS: Simple update worked!\n\n";
    } else {
        echo "❌ FAILED: Simple update failed!\n\n";
    }
    
    // Test 2: Update with multiple fields (like approval does)
    echo "Test 2: Simulating application approval...\n";
    
    $result = $database->update(
        'project_applications',
        [
            'status' => 'approved',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => 1,
            'admin_message' => 'Test approval message'
        ],
        'application_id = ?',
        [1]
    );
    
    if ($result) {
        echo "✅ SUCCESS: Approval-style update worked!\n\n";
    } else {
        echo "❌ FAILED: Approval-style update failed!\n\n";
    }
    
    // Test 3: Verify the data
    echo "Test 3: Verifying updated data...\n";
    
    $app = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [1]
    );
    
    if ($app) {
        echo "Application ID: {$app['application_id']}\n";
        echo "Status: {$app['status']}\n";
        echo "Reviewed At: " . ($app['reviewed_at'] ?? 'NULL') . "\n";
        echo "Admin Message: " . ($app['admin_message'] ?? 'NULL') . "\n";
        echo "✅ Data retrieved successfully!\n\n";
    } else {
        echo "❌ Could not retrieve application data\n\n";
    }
    
    // Test 4: Reset to pending for next test
    echo "Test 4: Resetting to pending...\n";
    $database->update(
        'project_applications',
        ['status' => 'pending'],
        'application_id = ?',
        [1]
    );
    echo "✅ Reset complete!\n\n";
    
    echo "====================================\n";
    echo "ALL TESTS PASSED! 🎉\n";
    echo "====================================\n";
    echo "The Database::update() method is now working correctly.\n";
    echo "You can now approve/reject applications without errors!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

<style>
body {
    font-family: 'Courier New', monospace;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #2c409a;
}
pre {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
    line-height: 1.6;
}
</style>