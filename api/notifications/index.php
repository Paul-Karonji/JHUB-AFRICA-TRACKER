<?php
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$notificationService = new Notification();

if (!$auth->isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$userType = $auth->getUserType();
$userId = $auth->getUserId();

switch ($method) {
    case 'GET':
        if (($_GET['scope'] ?? '') === 'unread-count') {
            jsonResponse(['success' => true, 'unread' => $notificationService->countUnread($userType, $userId)]);
        }
        $options = [
            'limit' => (int) ($_GET['limit'] ?? 20),
            'offset' => (int) ($_GET['offset'] ?? 0),
            'unread_only' => ($_GET['unread_only'] ?? '0') === '1'
        ];
        jsonResponse($notificationService->getNotifications($userType, $userId, $options));
        break;

    case 'POST':
        $payload = getRequestPayload();
        if (($_GET['action'] ?? '') === 'mark-read' || isset($payload['notification_id'])) {
            $notificationId = $payload['notification_id'] ?? null;
            if (!$notificationId) {
                jsonResponse(['success' => false, 'error' => 'notification_id is required'], 400);
            }
            jsonResponse($notificationService->markAsRead($notificationId, $userType, $userId));
            break;
        }
        if (($_GET['action'] ?? '') === 'mark-all-read') {
            jsonResponse($notificationService->markAllAsRead($userType, $userId));
            break;
        }
        if ($userType !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Only administrators can create notifications'], 403);
        }
        $result = $notificationService->create($payload);
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    case 'DELETE':
        $notificationId = $_GET['notification_id'] ?? null;
        if (!$notificationId) {
            jsonResponse(['success' => false, 'error' => 'notification_id is required'], 400);
        }
        $result = $notificationService->delete($notificationId, $userType, $userId);
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
