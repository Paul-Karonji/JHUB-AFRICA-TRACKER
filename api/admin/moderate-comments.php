<?php
// api/admin/moderate-comments.php
// API for admin comment moderation
header('Content-Type: application/json');
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

// Require admin authentication
if (!$auth->isLoggedIn() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Administrative access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$adminId = $auth->getUserId();

try {
    switch ($method) {
        case 'GET':
            // Get pending comments for review
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $pendingComments = getPendingComments($limit);
            
            echo json_encode([
                'success' => true,
                'comments' => $pendingComments,
                'total' => count($pendingComments)
            ]);
            break;

        case 'POST':
            // Handle comment approval/rejection
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid request data');
            }

            // Validate CSRF token
            if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
                throw new Exception('Invalid security token');
            }

            if (!isset($input['action']) || !isset($input['comment_id'])) {
                throw new Exception('Action and comment ID are required');
            }

            $action = $input['action'];
            $commentId = intval($input['comment_id']);

            // Verify comment exists and is pending
            $comment = $database->getRow("
                SELECT * FROM comments 
                WHERE comment_id = ? AND commenter_type = 'investor' 
                AND is_approved = 0 AND is_deleted = 0
            ", [$commentId]);

            if (!$comment) {
                throw new Exception('Comment not found or already processed');
            }

            switch ($action) {
                case 'approve':
                    $adminNotes = $input['admin_notes'] ?? '';
                    $result = approveComment($commentId, $adminId, $adminNotes);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Comment approved successfully'
                        ]);
                    } else {
                        throw new Exception('Failed to approve comment');
                    }
                    break;

                case 'reject':
                    $reason = $input['reason'] ?? 'No reason provided';
                    $result = rejectComment($commentId, $adminId, $reason);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Comment rejected successfully'
                        ]);
                    } else {
                        throw new Exception('Failed to reject comment');
                    }
                    break;

                case 'bulk_approve':
                    if (!isset($input['comment_ids']) || !is_array($input['comment_ids'])) {
                        throw new Exception('Comment IDs array required for bulk approval');
                    }

                    $approvedCount = 0;
                    $failedCount = 0;
                    $adminNotes = $input['admin_notes'] ?? 'Bulk approved';

                    foreach ($input['comment_ids'] as $commentId) {
                        $commentId = intval($commentId);
                        if (approveComment($commentId, $adminId, $adminNotes)) {
                            $approvedCount++;
                        } else {
                            $failedCount++;
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => "Bulk approval completed: {$approvedCount} approved, {$failedCount} failed",
                        'approved' => $approvedCount,
                        'failed' => $failedCount
                    ]);
                    break;

                case 'bulk_reject':
                    if (!isset($input['comment_ids']) || !is_array($input['comment_ids'])) {
                        throw new Exception('Comment IDs array required for bulk rejection');
                    }

                    $rejectedCount = 0;
                    $failedCount = 0;
                    $reason = $input['reason'] ?? 'Bulk rejected';

                    foreach ($input['comment_ids'] as $commentId) {
                        $commentId = intval($commentId);
                        if (rejectComment($commentId, $adminId, $reason)) {
                            $rejectedCount++;
                        } else {
                            $failedCount++;
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => "Bulk rejection completed: {$rejectedCount} rejected, {$failedCount} failed",
                        'rejected' => $rejectedCount,
                        'failed' => $failedCount
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action specified');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Comment moderation error: ' . $e->getMessage());
}
?>