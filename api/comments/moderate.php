<?php
/**
 * api/comments/moderate.php
 * API endpoint for approving or rejecting comments
 * Admin only
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

    // Validate action
    if (!isset($input['action']) || !in_array($input['action'], ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }

    $action = $input['action'];
    $adminId = $auth->getUserId();
    $adminName = $_SESSION['admin_name'] ?? 'Admin';

    // Handle bulk or single comment moderation
    $commentIds = [];
    
    if (isset($input['comment_ids']) && is_array($input['comment_ids'])) {
        // Bulk action
        $commentIds = array_map('intval', $input['comment_ids']);
    } elseif (isset($input['comment_id'])) {
        // Single comment
        $commentIds = [intval($input['comment_id'])];
    } else {
        throw new Exception('No comment ID(s) provided');
    }

    if (empty($commentIds)) {
        throw new Exception('No valid comment IDs provided');
    }

    $database->beginTransaction();

    $successCount = 0;
    $failCount = 0;

    foreach ($commentIds as $commentId) {
        // Get comment details
        $comment = $database->getRow("
            SELECT c.*, p.project_name 
            FROM comments c
            INNER JOIN projects p ON c.project_id = p.project_id
            WHERE c.comment_id = ? AND c.is_deleted = 0
        ", [$commentId]);

        if (!$comment) {
            $failCount++;
            continue;
        }

        if ($action === 'approve') {
            // Approve the comment
            $updated = $database->update('comments', [
                'is_approved' => 1,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s')
            ], 'comment_id = ?', [$commentId]);

            if ($updated) {
                // Log activity
                logActivity(
                    'admin',
                    $adminId,
                    'comment_approved',
                    "Approved comment from {$comment['commenter_name']} on project: {$comment['project_name']}",
                    $comment['project_id'],
                    [
                        'comment_id' => $commentId,
                        'commenter_type' => $comment['commenter_type'],
                        'commenter_email' => $comment['commenter_email']
                    ]
                );

                // Optionally: Send email notification to commenter
                if ($comment['commenter_email'] && $comment['commenter_type'] === 'investor') {
                    $database->insert('email_notifications', [
                        'recipient_email' => $comment['commenter_email'],
                        'recipient_name' => $comment['commenter_name'],
                        'subject' => 'Your Comment Has Been Approved',
                        'message_body' => "Hello {$comment['commenter_name']},\n\nYour comment on the project '{$comment['project_name']}' has been approved and is now visible to the public.\n\nThank you for your contribution!\n\nBest regards,\nJHUB AFRICA Team",
                        'notification_type' => 'general',
                        'related_project_id' => $comment['project_id'],
                        'status' => 'pending'
                    ]);
                }

                $successCount++;
            } else {
                $failCount++;
            }

        } elseif ($action === 'reject') {
            // Reject the comment (soft delete)
            $updated = $database->update('comments', [
                'is_deleted' => 1,
                'admin_notes' => 'Rejected by admin during moderation'
            ], 'comment_id = ?', [$commentId]);

            if ($updated) {
                // Log activity
                logActivity(
                    'admin',
                    $adminId,
                    'comment_rejected',
                    "Rejected comment from {$comment['commenter_name']} on project: {$comment['project_name']}",
                    $comment['project_id'],
                    [
                        'comment_id' => $commentId,
                        'commenter_type' => $comment['commenter_type'],
                        'commenter_email' => $comment['commenter_email']
                    ]
                );

                // Optionally: Send email notification to commenter (optional, might not want to notify)
                // Uncomment if you want to notify rejected comments
                /*
                if ($comment['commenter_email'] && $comment['commenter_type'] === 'investor') {
                    $database->insert('email_notifications', [
                        'recipient_email' => $comment['commenter_email'],
                        'recipient_name' => $comment['commenter_name'],
                        'subject' => 'Comment Not Approved',
                        'message_body' => "Hello {$comment['commenter_name']},\n\nThank you for your interest. Unfortunately, your comment on the project '{$comment['project_name']}' did not meet our community guidelines and was not approved.\n\nBest regards,\nJHUB AFRICA Team",
                        'notification_type' => 'general',
                        'related_project_id' => $comment['project_id'],
                        'status' => 'pending'
                    ]);
                }
                */

                $successCount++;
            } else {
                $failCount++;
            }
        }
    }

    $database->commit();

    // Prepare response message
    $totalComments = count($commentIds);
    $actionText = $action === 'approve' ? 'approved' : 'rejected';
    
    if ($successCount === $totalComments) {
        $message = $totalComments === 1 
            ? "Comment successfully {$actionText}!"
            : "{$successCount} comments successfully {$actionText}!";
    } elseif ($successCount > 0) {
        $message = "{$successCount} of {$totalComments} comments {$actionText}. {$failCount} failed.";
    } else {
        throw new Exception("Failed to {$action} comment(s)");
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action,
        'processed' => $successCount,
        'failed' => $failCount,
        'total' => $totalComments
    ]);

} catch (Exception $e) {
    $database->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Comment moderation error: ' . $e->getMessage());
}