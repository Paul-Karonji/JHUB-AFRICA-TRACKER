<?php
/**
 * check-application.php
 * Place in project ROOT
 * Access: http://localhost/jhub-africa-tracker/check-application.php?id=11
 */

require_once 'includes/init.php';

// Get application ID from URL
$appId = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "<h1>Application Details Check</h1>";
echo "<hr>";

if ($appId === 0) {
    echo "<p>Please provide an application ID in the URL:</p>";
    echo "<p><code>check-application.php?id=11</code></p>";
    
    // Show all applications
    echo "<h3>Available Applications:</h3>";
    $apps = $database->getRows("SELECT application_id, project_name, status, applied_at FROM project_applications ORDER BY application_id DESC");
    
    if ($apps) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Project Name</th><th>Status</th><th>Applied At</th><th>Action</th></tr>";
        foreach ($apps as $app) {
            echo "<tr>";
            echo "<td>{$app['application_id']}</td>";
            echo "<td>{$app['project_name']}</td>";
            echo "<td><strong>{$app['status']}</strong></td>";
            echo "<td>{$app['applied_at']}</td>";
            echo "<td><a href='?id={$app['application_id']}'>Check Details</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    exit;
}

echo "<h3>Checking Application ID: {$appId}</h3>";

// Get application details
$application = $database->getRow(
    "SELECT * FROM project_applications WHERE application_id = ?",
    [$appId]
);

if (!$application) {
    echo "<div style='background:#ffebee; color:#c62828; padding:15px; border-radius:5px;'>";
    echo "<strong>❌ APPLICATION NOT FOUND</strong><br>";
    echo "Application ID {$appId} does not exist in the database.";
    echo "</div>";
    
    echo "<h4>Possible Reasons:</h4>";
    echo "<ul>";
    echo "<li>The application was deleted</li>";
    echo "<li>Wrong application ID</li>";
    echo "<li>Database connection issue</li>";
    echo "</ul>";
    
    exit;
}

echo "<div style='background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:5px;'>";
echo "<strong>✅ APPLICATION FOUND</strong>";
echo "</div>";

echo "<h3>Application Details:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>";

foreach ($application as $key => $value) {
    $displayValue = $value;
    
    // Highlight important fields
    $style = "";
    if ($key === 'status') {
        if ($value === 'pending') {
            $style = "background:#fff3cd; font-weight:bold;";
        } elseif ($value === 'approved') {
            $style = "background:#d4edda; font-weight:bold;";
        } elseif ($value === 'rejected') {
            $style = "background:#f8d7da; font-weight:bold;";
        }
    }
    
    // Truncate long values
    if (strlen($displayValue) > 100) {
        $displayValue = substr($displayValue, 0, 100) . '...';
    }
    
    echo "<tr>";
    echo "<td style='font-weight:bold; width:200px;'>{$key}</td>";
    echo "<td style='{$style}'>" . htmlspecialchars($displayValue) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

// Check if can be approved
echo "<h3>Can This Application Be Approved?</h3>";

if ($application['status'] !== 'pending') {
    echo "<div style='background:#ffebee; color:#c62828; padding:15px; border-radius:5px;'>";
    echo "<strong>❌ CANNOT BE APPROVED</strong><br>";
    echo "This application has already been <strong>{$application['status']}</strong>.";
    echo "</div>";
    
    if ($application['reviewed_at']) {
        echo "<p><strong>Reviewed at:</strong> {$application['reviewed_at']}</p>";
    }
    if ($application['reviewed_by']) {
        echo "<p><strong>Reviewed by Admin ID:</strong> {$application['reviewed_by']}</p>";
    }
} else {
    echo "<div style='background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:5px;'>";
    echo "<strong>✅ CAN BE APPROVED</strong><br>";
    echo "This application is pending and ready for review.";
    echo "</div>";
    
    // Show test approve button if logged in as admin
    if ($auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_ADMIN) {
        $token = $auth->generateCSRFToken();
        
        echo "<hr>";
        echo "<h4>Test Approval (Live Test)</h4>";
        echo "<button onclick='testApprove()' style='background:#28a745; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:16px;'>
                <i class='fas fa-check'></i> Test Approve This Application
              </button>";
        echo "<div id='approveResult' style='margin-top:20px;'></div>";
        
        echo "<script>
        function testApprove() {
            if (!confirm('This will ACTUALLY approve application {$appId}. Continue?')) {
                return;
            }
            
            const resultDiv = document.getElementById('approveResult');
            resultDiv.innerHTML = '<p style=\"background:#fff3cd; padding:10px;\">⏳ Sending approval request...</p>';
            
            fetch('api/applications/review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: {$appId},
                    action: 'approve',
                    admin_message: 'Test approval from diagnostic tool',
                    csrf_token: '{$token}'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        resultDiv.innerHTML = '<div style=\"background:#d4edda; color:#155724; padding:15px; border-radius:5px;\">' +
                                            '<h4>✅ SUCCESS!</h4>' +
                                            '<p>' + data.message + '</p>' +
                                            '<p><strong>Project ID:</strong> ' + data.project_id + '</p>' +
                                            '<p><a href=\"dashboards/admin/applications.php\">View Applications</a></p>' +
                                            '</div>';
                    } else {
                        resultDiv.innerHTML = '<div style=\"background:#f8d7da; color:#721c24; padding:15px; border-radius:5px;\">' +
                                            '<h4>❌ ERROR</h4>' +
                                            '<p>' + data.message + '</p>' +
                                            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
                                            '</div>';
                    }
                } catch (e) {
                    resultDiv.innerHTML = '<div style=\"background:#f8d7da; color:#721c24; padding:15px; border-radius:5px;\">' +
                                        '<h4>❌ PARSE ERROR</h4>' +
                                        '<p>Response is not valid JSON</p>' +
                                        '<pre>' + text + '</pre>' +
                                        '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style=\"background:#f8d7da; color:#721c24; padding:15px; border-radius:5px;\">' +
                                    '<h4>❌ NETWORK ERROR</h4>' +
                                    '<p>' + error + '</p>' +
                                    '</div>';
            });
        }
        </script>";
    } else {
        echo "<p style='color:red;'>You must be logged in as admin to test approval.</p>";
    }
}
?>