<?php
require_once __DIR__ . '/../includes/init.php';

$commentService = new Comment();
$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['comment_id'])) {
            $comment = $commentService->getComment($_GET['comment_id']);
            if (!$comment) {
                jsonResponse(['success' => false, 'error' => 'Comment not found'], 404);
            }
            jsonResponse(['success' => true, 'comment' => $comment]);
        }
        $projectId = $_GET['project_id'] ?? null;
        if (!$projectId) {
            jsonResponse(['success' => false, 'error' => 'project_id is required'], 400);
        }
        $includeReplies = ($_GET['include_replies'] ?? '1') !== '0';
        $limit = $_GET['limit'] ?? null;
        jsonResponse($commentService->getProjectComments($projectId, $includeReplies, 'newest_first', $limit));
        break;

    case 'POST':
        $payload = getRequestPayload();
        requireKeys($payload, ['project_id', 'comment_text']);
        $userType = 'public';
        $userId = null;
        $commenterName = $payload['commenter_name'] ?? null;
        if ($auth->isAuthenticated()) {
            $userType = $auth->getUserType();
            $userId = $auth->getUserId();
            if ($userType === 'project') {
                $commenterName = $_SESSION['project_name'] ?? 'Project Team';
            } elseif ($userType === 'mentor') {
                $commenterName = $_SESSION['name'] ?? 'Mentor';
            } elseif ($userType === 'admin') {
                $commenterName = $_SESSION['username'] ?? 'Admin';
            }
        } else {
            $userType = $payload['user_type'] ?? 'public';
        }
        $result = $commentService->addComment(
            $payload['project_id'],
            $userType,
            $userId,
            $payload['comment_text'],
            $payload['parent_id'] ?? null,
            $commenterName
        );
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    case 'PUT':
        requireMethod('PUT');
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $payload = getRequestPayload();
        requireKeys($payload, ['comment_id', 'comment_text']);
        $result = $commentService->updateComment(
            $payload['comment_id'],
            $payload['comment_text'],
            $auth->getUserType(),
            $auth->getUserId()
        );
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    case 'DELETE':
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $commentId = $_GET['comment_id'] ?? null;
        if (!$commentId) {
            jsonResponse(['success' => false, 'error' => 'comment_id is required'], 400);
        }
        $result = $commentService->deleteComment($commentId, $auth->getUserType(), $auth->getUserId());
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
