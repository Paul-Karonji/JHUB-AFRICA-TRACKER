<?php
/**
 * test-review-api.php
 * Place this in your project ROOT directory
 * Access: http://localhost/jhub-africa-tracker/test-review-api.php
 * 
 * This will test if the review API is reachable and show any errors
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Review Test</h1>";
echo "<hr>";

// Test 1: Check if init.php loads
echo "<h3>Test 1: Loading init.php</h3>";
try {
    require_once 'includes/init.php';
    echo "✅ init.php loaded successfully<br>";
    echo "Auth class exists: " . (class_exists('Auth') ? 'YES' : 'NO') . "<br>";
    echo "Database class exists: " . (class_exists('Database') ? 'YES' : 'NO') . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<hr>";

// Test 2: Check Auth methods
echo "<h3>Test 2: Auth Methods</h3>";
echo "isLoggedIn method exists: " . (method_exists($auth, 'isLoggedIn') ? 'YES' : 'NO') . "<br>";
echo "getUserType method exists: " . (method_exists($auth, 'getUserType') ? 'YES' : 'NO') . "<br>";
echo "getUserId method exists: " . (method_exists($auth, 'getUserId') ? 'YES' : 'NO') . "<br>";
echo "validateCSRFToken method exists: " . (method_exists($auth, 'validateCSRFToken') ? 'YES' : 'NO') . "<br>";
echo "Current user logged in: " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "<br>";
if ($auth->isLoggedIn()) {
    echo "User type: " . $auth->getUserType() . "<br>";
    echo "User ID: " . $auth->getUserId() . "<br>";
}

echo "<hr>";

// Test 3: Check Database methods
echo "<h3>Test 3: Database Methods</h3>";
echo "update method exists: " . (method_exists($database, 'update') ? 'YES' : 'NO') . "<br>";
echo "insert method exists: " . (method_exists($database, 'insert') ? 'YES' : 'NO') . "<br>";
echo "getRow method exists: " . (method_exists($database, 'getRow') ? 'YES' : 'NO') . "<br>";

echo "<hr>";

// Test 4: Check Database connection
echo "<h3>Test 4: Database Connection</h3>";
try {
    $testQuery = $database->getRow("SELECT COUNT(*) as count FROM project_applications");
    echo "✅ Database connection working<br>";
    echo "Total applications: " . $testQuery['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 5: Test a simple database update (dry run)
echo "<h3>Test 5: Test Database Update Method</h3>";
try {
    // Get the update method signature via reflection
    $reflection = new ReflectionMethod('Database', 'update');
    $params = $reflection->getParameters();
    
    echo "Update method parameters:<br>";
    foreach ($params as $param) {
        echo "- " . $param->getName();
        if ($param->isOptional()) {
            echo " (optional)";
        }
        echo "<br>";
    }
    
    // Look at the actual method code
    $filename = $reflection->getFileName();
    $start_line = $reflection->getStartLine() - 1;
    $end_line = $reflection->getEndLine();
    $length = $end_line - $start_line;
    
    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));
    
    echo "<h4>Update Method Code:</h4>";
    echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto;'>";
    echo htmlspecialchars($body);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 6: Check if review.php file exists and is readable
echo "<h3>Test 6: Review API File</h3>";
$reviewFile = __DIR__ . '/api/applications/review.php';
echo "File path: {$reviewFile}<br>";
echo "File exists: " . (file_exists($reviewFile) ? 'YES' : 'NO') . "<br>";
echo "File readable: " . (is_readable($reviewFile) ? 'YES' : 'NO') . "<br>";
if (file_exists($reviewFile)) {
    echo "File size: " . filesize($reviewFile) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($reviewFile)) . "<br>";
}

echo "<hr>";

// Test 7: Check for BOM or whitespace
echo "<h3>Test 7: Check Database.php for BOM/Whitespace</h3>";
$dbFile = __DIR__ . '/classes/Database.php';
if (file_exists($dbFile)) {
    $content = file_get_contents($dbFile);
    $first_chars = substr($content, 0, 10);
    
    echo "First 10 bytes (hex): ";
    for ($i = 0; $i < strlen($first_chars); $i++) {
        echo dechex(ord($first_chars[$i])) . " ";
    }
    echo "<br>";
    
    // Check for BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "⚠️ WARNING: File has UTF-8 BOM!<br>";
    } else {
        echo "✅ No BOM detected<br>";
    }
    
    // Check if starts with <?php
    if (substr(ltrim($content), 0, 5) !== '<?php') {
        echo "⚠️ WARNING: File doesn't start with <?php tag!<br>";
    } else {
        echo "✅ File starts with <?php correctly<br>";
    }
}

echo "<hr>";
echo "<h3>✅ All Tests Complete</h3>";
echo "<p>If you're logged in as admin, try this mock request:</p>";

if ($auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_ADMIN) {
    $token = $auth->generateCSRFToken();
    echo "<button onclick='testReviewAPI()'>Test Review API Call</button>";
    echo "<div id='result' style='margin-top:20px; padding:10px; background:#f5f5f5;'></div>";
    
    echo "<script>
    function testReviewAPI() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>Sending request...</p>';
        
        fetch('api/applications/review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: 999999, // Fake ID to test error handling
                action: 'approve',
                csrf_token: '{$token}'
            })
        })
        .then(response => {
            resultDiv.innerHTML += '<p>Status: ' + response.status + '</p>';
            return response.text();
        })
        .then(text => {
            resultDiv.innerHTML += '<h4>Response:</h4>';
            resultDiv.innerHTML += '<pre style=\"max-height:300px; overflow:auto;\">' + text + '</pre>';
            
            try {
                const json = JSON.parse(text);
                resultDiv.innerHTML += '<h4>Parsed JSON:</h4>';
                resultDiv.innerHTML += '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
            } catch (e) {
                resultDiv.innerHTML += '<p style=\"color:red;\">Response is not valid JSON!</p>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML += '<p style=\"color:red;\">Error: ' + error + '</p>';
        });
    }
    </script>";
} else {
    echo "<p style='color:red;'>You must be logged in as admin to test the API call.</p>";
}
?>