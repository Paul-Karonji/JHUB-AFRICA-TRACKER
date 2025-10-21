<?php
/**
 * api/comments/bulk-approve.php
 * One-time bulk approval of existing comments
 * This is useful for migrating existing comments to the new approval system
 */

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require admin authentication
if (!$auth->isValidSession() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
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

    $adminId = $auth->getUserId();
    $action = $input['action'] ?? '';

    if ($action === 'approve_internal_comments') {
        // Approve all comments from admin, mentor, and innovator users
        // These should have been auto-approved but weren't due to the old system
        
        $database->beginTransaction();
        
        // Get all pending internal comments (admin, mentor, innovator)
        $internalComments = $database->getRows("
            SELECT comment_id, commenter_name, commenter_type, project_id
            FROM comments 
            WHERE is_deleted = 0 
              AND is_approved = 0 
              AND commenter_type IN ('admin', 'mentor', 'innovator')
        ");
        
        $approvedCount = 0;
        
        foreach ($internalComments as $comment) {
            $updated = $database->update('comments', [
                'is_approved' => 1,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'admin_notes' => 'Auto-approved during system migration'
            ], 'comment_id = ?', [$comment['comment_id']]);
            
            if ($updated) {
                $approvedCount++;
            }
        }
        
        $database->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully auto-approved {$approvedCount} internal comment(s)!",
            'approved_count' => $approvedCount
        ]);
        
    } elseif ($action === 'approve_all_existing') {
        // Approve ALL existing comments (including investor ones)
        // Use this if you trust all existing comments
        
        $database->beginTransaction();
        
        // Get all pending comments
        $allComments = $database->getRows("
            SELECT comment_id 
            FROM comments 
            WHERE is_deleted = 0 AND is_approved = 0
        ");
        
        $approvedCount = 0;
        
        foreach ($allComments as $comment) {
            $updated = $database->update('comments', [
                'is_approved' => 1,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'admin_notes' => 'Bulk approved during system migration'
            ], 'comment_id = ?', [$comment['comment_id']]);
            
            if ($updated) {
                $approvedCount++;
            }
        }
        
        $database->commit();
        
        // Log this important action
        logActivity(
            'admin',
            $adminId,
            'bulk_comments_approved',
            "Bulk approved {$approvedCount} existing comments during system migration",
            null,
            ['approved_count' => $approvedCount]
        );
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully bulk-approved {$approvedCount} comment(s)!",
            'approved_count' => $approvedCount
        ]);
        
    } else {
        throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    if (isset($database)) {
        $database->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Bulk approve error: ' . $e->getMessage());
}