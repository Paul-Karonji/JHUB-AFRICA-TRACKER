<?php
// verify-database.php
// Comprehensive Database Verification Script for JHUB Africa Project Tracker
// This script checks all tables, columns, indexes, and constraints
// DELETE THIS FILE after verification for security

session_start();

// Include only what we need for database connection
require_once 'config/database.php';
require_once 'classes/Database.php';

try {
    $database = Database::getInstance();
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Simple admin check
$isAdmin = false;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $isAdmin = true;
}

if (!$isAdmin) {
    die('<!DOCTYPE html>
<html>
<head>
    <title>Database Verification - Admin Access Required</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error">Admin Access Required</h1>
        <p>Please <a href="auth/admin-login.php">login as an administrator</a> to run the database verification script.</p>
    </div>
</body>
</html>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>JHUB Africa Tracker - Database Verification</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5; 
            line-height: 1.6;
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #2c5aa0; border-bottom: 3px solid #2c5aa0; padding-bottom: 10px; }
        h2 { color: #1976d2; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        h3 { color: #424242; margin-top: 20px; }
        .success { color: #2e7d32; }
        .error { color: #d32f2f; }
        .warning { color: #f57c00; }
        .info { color: #1976d2; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .status-ok { background-color: #e8f5e8; color: #2e7d32; }
        .status-missing { background-color: #ffebee; color: #d32f2f; }
        .status-warning { background-color: #fff3e0; color: #f57c00; }
        .summary { 
            background: #e3f2fd; 
            border: 1px solid #2196f3; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .recommendations {
            background: #fff3e0;
            border: 1px solid #ff9800;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        ul { margin: 10px 0; padding-left: 30px; }
        li { margin: 5px 0; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 JHUB Africa Tracker - Database Verification</h1>
        <p>Comprehensive verification of all database tables, columns, indexes, and constraints.</p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?> | <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

        <?php
        
        // Define expected tables and their critical columns
        $expectedTables = [
            'admins' => [
                'primary_key' => 'admin_id',
                'required_columns' => ['admin_id', 'username', 'password', 'email', 'name', 'is_active', 'created_at'],
                'unique_keys' => ['username'],
                'description' => 'System administrators'
            ],
            'mentors' => [
                'primary_key' => 'mentor_id',
                'required_columns' => ['mentor_id', 'name', 'email', 'password', 'bio', 'area_of_expertise', 'is_active', 'created_at'],
                'unique_keys' => ['email'],
                'description' => 'Project mentors'
            ],
            'projects' => [
                'primary_key' => 'project_id',
                'required_columns' => ['project_id', 'project_name', 'description', 'current_stage', 'status', 'project_lead_name', 'project_lead_email', 'profile_name', 'password', 'created_at'],
                'unique_keys' => ['profile_name'],
                'description' => 'Innovation projects'
            ],
            'project_applications' => [
                'primary_key' => 'application_id',
                'required_columns' => ['application_id', 'project_name', 'description', 'project_lead_name', 'project_lead_email', 'profile_name', 'password', 'status', 'applied_at'],
                'unique_keys' => ['profile_name'],
                'description' => 'Project applications pending review'
            ],
            'project_innovators' => [
                'primary_key' => 'pi_id',
                'required_columns' => ['pi_id', 'project_id', 'name', 'email', 'role', 'added_at', 'is_active'],
                'foreign_keys' => ['project_id' => 'projects.project_id'],
                'description' => 'Project team members'
            ],
            'project_mentors' => [
                'primary_key' => 'pm_id',
                'required_columns' => ['pm_id', 'project_id', 'mentor_id', 'assigned_at', 'is_active'],
                'foreign_keys' => ['project_id' => 'projects.project_id', 'mentor_id' => 'mentors.mentor_id'],
                'unique_keys' => ['project_id', 'mentor_id'],
                'description' => 'Mentor-project assignments'
            ],
            'comments' => [
                'primary_key' => 'comment_id',
                'required_columns' => ['comment_id', 'project_id', 'commenter_type', 'commenter_name', 'comment_text', 'created_at', 'is_deleted'],
                'new_columns' => ['is_approved', 'approved_by', 'approved_at', 'admin_notes'], // New moderation columns
                'foreign_keys' => ['project_id' => 'projects.project_id'],
                'description' => 'Project comments and feedback'
            ],
            'mentor_stage_approvals' => [
                'primary_key' => 'approval_id',
                'required_columns' => ['approval_id', 'project_id', 'mentor_id', 'current_stage', 'approved_for_next_stage', 'created_at'],
                'foreign_keys' => ['project_id' => 'projects.project_id', 'mentor_id' => 'mentors.mentor_id'],
                'unique_keys' => ['project_id', 'mentor_id', 'current_stage'],
                'description' => 'Mentor consensus for stage progression (NEW FEATURE)',
                'is_new' => true
            ],
            'mentor_resources' => [
                'primary_key' => 'resource_id',
                'required_columns' => ['resource_id', 'project_id', 'mentor_id', 'title', 'description', 'resource_type', 'created_at'],
                'foreign_keys' => ['project_id' => 'projects.project_id', 'mentor_id' => 'mentors.mentor_id'],
                'description' => 'Mentor-shared resources'
            ],
            'project_assessments' => [
                'primary_key' => 'assessment_id',
                'required_columns' => ['assessment_id', 'project_id', 'mentor_id', 'title', 'description', 'is_completed', 'created_at'],
                'foreign_keys' => ['project_id' => 'projects.project_id', 'mentor_id' => 'mentors.mentor_id'],
                'description' => 'Project assessment checklists'
            ],
            'learning_objectives' => [
                'primary_key' => 'objective_id',
                'required_columns' => ['objective_id', 'project_id', 'mentor_id', 'title', 'description', 'is_completed', 'created_at'],
                'foreign_keys' => ['project_id' => 'projects.project_id', 'mentor_id' => 'mentors.mentor_id'],
                'description' => 'Learning objectives and tracking'
            ],
            'email_notifications' => [
                'primary_key' => 'notification_id',
                'required_columns' => ['notification_id', 'recipient_email', 'subject', 'message_body', 'notification_type', 'created_at'],
                'description' => 'Email notification queue'
            ],
            'activity_logs' => [
                'primary_key' => 'log_id',
                'required_columns' => ['log_id', 'user_type', 'user_id', 'action', 'description', 'created_at'],
                'description' => 'System activity tracking'
            ],
            'system_settings' => [
                'primary_key' => 'setting_id',
                'required_columns' => ['setting_id', 'setting_key', 'setting_value', 'setting_type', 'created_at'],
                'unique_keys' => ['setting_key'],
                'description' => 'System configuration settings'
            ],
            'sessions' => [
                'primary_key' => 'session_id',
                'required_columns' => ['session_id', 'user_type', 'user_id', 'last_activity', 'created_at'],
                'description' => 'User session management'
            ],
            'password_reset_tokens' => [
                'primary_key' => 'token_id',
                'required_columns' => ['token_id', 'user_type', 'user_id', 'token', 'expires_at', 'created_at'],
                'description' => 'Password reset functionality'
            ]
        ];

        // Check database connection and basic info
        echo "<h2>📊 Database Connection & Basic Info</h2>";
        try {
            $dbVersion = $database->getRow("SELECT VERSION() as version");
            $dbName = $database->getRow("SELECT DATABASE() as db_name");
            $charset = $database->getRow("SELECT @@character_set_database as charset, @@collation_database as collation");
            
            echo "<table>";
            echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
            echo "<tr><td>Database Version</td><td>{$dbVersion['version']}</td><td class='status-ok'>✓ Connected</td></tr>";
            echo "<tr><td>Database Name</td><td>{$dbName['db_name']}</td><td class='status-ok'>✓ Active</td></tr>";
            echo "<tr><td>Character Set</td><td>{$charset['charset']}</td><td class='status-ok'>✓ UTF8</td></tr>";
            echo "<tr><td>Collation</td><td>{$charset['collation']}</td><td class='status-ok'>✓ Unicode</td></tr>";
            echo "</table>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        // Get all existing tables
        $existingTables = [];
        try {
            $tables = $database->getRows("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $existingTables[] = $tableName;
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Could not retrieve table list: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        // Table verification
        echo "<h2>🗂️ Table Structure Verification</h2>";
        
        $totalTables = count($expectedTables);
        $existingTablesCount = 0;
        $missingTables = [];
        $tableIssues = [];

        echo "<table>";
        echo "<tr><th>Table Name</th><th>Description</th><th>Status</th><th>Records</th><th>Details</th></tr>";

        foreach ($expectedTables as $tableName => $tableInfo) {
            $tableExists = in_array($tableName, $existingTables);
            $recordCount = 0;
            $status = '';
            $details = [];

            if ($tableExists) {
                $existingTablesCount++;
                try {
                    $count = $database->count($tableName);
                    $recordCount = $count;
                    
                    // Check columns
                    $columns = $database->getRows("DESCRIBE `$tableName`");
                    $existingColumns = array_column($columns, 'Field');
                    
                    $missingColumns = array_diff($tableInfo['required_columns'], $existingColumns);
                    
                    if (empty($missingColumns)) {
                        if (isset($tableInfo['new_columns'])) {
                            // Check if new columns exist (for features like comment moderation)
                            $missingNewColumns = array_diff($tableInfo['new_columns'], $existingColumns);
                            if (empty($missingNewColumns)) {
                                $status = "<span class='status-ok'>✓ Complete (with new features)</span>";
                            } else {
                                $status = "<span class='status-warning'>⚠ Missing new features</span>";
                                $details[] = "Missing new columns: " . implode(', ', $missingNewColumns);
                            }
                        } else {
                            $status = "<span class='status-ok'>✓ Complete</span>";
                        }
                    } else {
                        $status = "<span class='status-missing'>❌ Missing columns</span>";
                        $details[] = "Missing columns: " . implode(', ', $missingColumns);
                        $tableIssues[] = $tableName;
                    }
                    
                } catch (Exception $e) {
                    $status = "<span class='status-missing'>❌ Error</span>";
                    $details[] = "Error: " . $e->getMessage();
                    $tableIssues[] = $tableName;
                }
            } else {
                $status = "<span class='status-missing'>❌ Missing</span>";
                $missingTables[] = $tableName;
                $details[] = "Table does not exist";
            }

            $tableLabel = $tableName;
            if (isset($tableInfo['is_new']) && $tableInfo['is_new']) {
                $tableLabel .= " <span style='color: #9c27b0;'>(NEW)</span>";
            }

            echo "<tr>";
            echo "<td><strong>$tableLabel</strong></td>";
            echo "<td>" . htmlspecialchars($tableInfo['description']) . "</td>";
            echo "<td>$status</td>";
            echo "<td>" . number_format($recordCount) . "</td>";
            echo "<td>" . (empty($details) ? '-' : implode('<br>', $details)) . "</td>";
            echo "</tr>";
        }

        echo "</table>";

        // Check for unexpected tables
        $unexpectedTables = array_diff($existingTables, array_keys($expectedTables));
        if (!empty($unexpectedTables)) {
            echo "<h3>📋 Additional Tables Found</h3>";
            echo "<p class='info'>These tables exist but are not part of the standard schema:</p>";
            echo "<ul>";
            foreach ($unexpectedTables as $table) {
                $count = 0;
                try {
                    $count = $database->count($table);
                } catch (Exception $e) {
                    // Ignore errors for unexpected tables
                }
                echo "<li><strong>$table</strong> (" . number_format($count) . " records)</li>";
            }
            echo "</ul>";
        }

        // Feature-specific verification
        echo "<h2>🆕 New Features Verification</h2>";

        echo "<h3>1. Mentor Consensus System</h3>";
        $consensusStatus = [];
        
        // Check mentor_stage_approvals table
        try {
            $database->query("SELECT 1 FROM mentor_stage_approvals LIMIT 1");
            $approvalCount = $database->count('mentor_stage_approvals');
            $consensusStatus[] = "<span class='success'>✓ mentor_stage_approvals table exists ($approvalCount records)</span>";
            
            // Check for active projects with mentor assignments
            $activeProjects = $database->count('projects', 'status = "active"');
            $mentorAssignments = $database->count('project_mentors', 'is_active = 1');
            $consensusStatus[] = "<span class='info'>ℹ $activeProjects active projects, $mentorAssignments mentor assignments</span>";
            
        } catch (Exception $e) {
            $consensusStatus[] = "<span class='error'>❌ mentor_stage_approvals table missing</span>";
        }

        echo "<ul>";
        foreach ($consensusStatus as $status) {
            echo "<li>$status</li>";
        }
        echo "</ul>";

        echo "<h3>2. Comment Moderation System</h3>";
        $moderationStatus = [];
        
        try {
            $database->query("SELECT is_approved, approved_by, approved_at FROM comments LIMIT 1");
            $moderationStatus[] = "<span class='success'>✓ Comment moderation columns exist</span>";
            
            $pendingComments = $database->count('comments', 'commenter_type = "investor" AND is_approved = 0 AND is_deleted = 0');
            $approvedComments = $database->count('comments', 'is_approved = 1');
            $totalComments = $database->count('comments', 'is_deleted = 0');
            
            $moderationStatus[] = "<span class='info'>ℹ $totalComments total comments ($approvedComments approved, $pendingComments pending)</span>";
            
        } catch (Exception $e) {
            $moderationStatus[] = "<span class='error'>❌ Comment moderation columns missing</span>";
        }

        echo "<ul>";
        foreach ($moderationStatus as $status) {
            echo "<li>$status</li>";
        }
        echo "</ul>";

        // System settings verification
        echo "<h3>3. System Settings</h3>";
        $settingsStatus = [];
        
        try {
            $requiredSettings = [
                'comment_moderation_enabled',
                'auto_approve_authenticated_comments', 
                'mentor_consensus_required',
                'admin_override_allowed'
            ];
            
            $existingSettings = $database->getRows("SELECT setting_key FROM system_settings WHERE setting_key IN ('" . implode("','", $requiredSettings) . "')");
            $existingKeys = array_column($existingSettings, 'setting_key');
            
            foreach ($requiredSettings as $setting) {
                if (in_array($setting, $existingKeys)) {
                    $settingsStatus[] = "<span class='success'>✓ $setting</span>";
                } else {
                    $settingsStatus[] = "<span class='warning'>⚠ $setting (missing)</span>";
                }
            }
            
        } catch (Exception $e) {
            $settingsStatus[] = "<span class='error'>❌ Could not check system settings</span>";
        }

        echo "<ul>";
        foreach ($settingsStatus as $status) {
            echo "<li>$status</li>";
        }
        echo "</ul>";

        // Data integrity checks
        echo "<h2>🔍 Data Integrity Checks</h2>";
        
        $integrityIssues = [];
        
        try {
            // Check for orphaned records
            $orphanedInnovators = $database->count('project_innovators pi LEFT JOIN projects p ON pi.project_id = p.project_id', 'p.project_id IS NULL');
            if ($orphanedInnovators > 0) {
                $integrityIssues[] = "$orphanedInnovators orphaned project innovators";
            }
            
            $orphanedMentors = $database->count('project_mentors pm LEFT JOIN projects p ON pm.project_id = p.project_id LEFT JOIN mentors m ON pm.mentor_id = m.mentor_id', 'p.project_id IS NULL OR m.mentor_id IS NULL');
            if ($orphanedMentors > 0) {
                $integrityIssues[] = "$orphanedMentors orphaned mentor assignments";
            }
            
            $orphanedComments = $database->count('comments c LEFT JOIN projects p ON c.project_id = p.project_id', 'p.project_id IS NULL');
            if ($orphanedComments > 0) {
                $integrityIssues[] = "$orphanedComments orphaned comments";
            }
            
            // Check for invalid stages
            $invalidStages = $database->count('projects', 'current_stage < 1 OR current_stage > 6');
            if ($invalidStages > 0) {
                $integrityIssues[] = "$invalidStages projects with invalid stages";
            }
            
        } catch (Exception $e) {
            $integrityIssues[] = "Could not perform all integrity checks: " . $e->getMessage();
        }

        if (empty($integrityIssues)) {
            echo "<p class='success'>✓ No data integrity issues found</p>";
        } else {
            echo "<div class='status-warning'>";
            echo "<h4>⚠ Data Integrity Issues Found:</h4>";
            echo "<ul>";
            foreach ($integrityIssues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
            echo "</div>";
        }

        // Summary
        echo "<div class='summary'>";
        echo "<h2>📊 Verification Summary</h2>";
        echo "<p><strong>Tables:</strong> $existingTablesCount / $totalTables found</p>";
        
        if (empty($missingTables) && empty($tableIssues)) {
            echo "<p class='success'><strong>✅ Database Status: EXCELLENT</strong></p>";
            echo "<p>All required tables and features are properly installed and configured.</p>";
        } elseif (!empty($missingTables) || !empty($tableIssues)) {
            echo "<p class='error'><strong>❌ Database Status: NEEDS ATTENTION</strong></p>";
            echo "<p>Some tables or features are missing or incomplete.</p>";
        } else {
            echo "<p class='warning'><strong>⚠ Database Status: MOSTLY COMPLETE</strong></p>";
            echo "<p>Database is functional but some new features may not be available.</p>";
        }
        echo "</div>";

        // Recommendations
        if (!empty($missingTables) || !empty($tableIssues)) {
            echo "<div class='recommendations'>";
            echo "<h2>🔧 Recommendations</h2>";
            
            if (!empty($missingTables)) {
                echo "<h3>Missing Tables:</h3>";
                echo "<ul>";
                foreach ($missingTables as $table) {
                    echo "<li><strong>$table:</strong> " . $expectedTables[$table]['description'];
                    if (isset($expectedTables[$table]['is_new'])) {
                        echo " <em>(required for new features)</em>";
                    }
                    echo "</li>";
                }
                echo "</ul>";
            }
            
            if (!empty($tableIssues)) {
                echo "<h3>Tables with Issues:</h3>";
                echo "<ul>";
                foreach ($tableIssues as $table) {
                    echo "<li><strong>$table:</strong> Check column structure and data integrity</li>";
                }
                echo "</ul>";
            }
            
            echo "<h3>Next Steps:</h3>";
            echo "<ol>";
            echo "<li>Run the manual database updates from the previous instructions</li>";
            echo "<li>Or use the SQL commands to create missing tables/columns</li>";
            echo "<li>Re-run this verification script to confirm fixes</li>";
            echo "<li>Test the application functionality</li>";
            echo "</ol>";
            echo "</div>";
        }

        ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
            <p><strong>🗑️ Delete this file (verify-database.php) after verification!</strong></p>
            <p><a href="dashboards/admin/">Return to Admin Dashboard</a> | <a href="javascript:window.print()">Print Report</a></p>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight critical issues
            const errorElements = document.querySelectorAll('.status-missing');
            errorElements.forEach(function(element) {
                element.style.fontWeight = 'bold';
            });
            
            // Add click to copy functionality for table names
            const tableNames = document.querySelectorAll('table td strong');
            tableNames.forEach(function(element) {
                element.style.cursor = 'pointer';
                element.title = 'Click to copy table name';
                element.addEventListener('click', function() {
                    navigator.clipboard.writeText(this.textContent).then(function() {
                        console.log('Table name copied to clipboard');
                    });
                });
            });
        });
    </script>
</body>
</html>