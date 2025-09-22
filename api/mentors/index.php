<?php
require_once __DIR__ . '/../includes/init.php';

$mentorService = new Mentor();
$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!$auth->isAuthenticated() || $auth->getUserType() !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
        }
        $filters = [
            'page' => $_GET['page'] ?? 1,
            'per_page' => $_GET['per_page'] ?? AppConfig::MENTORS_PER_PAGE,
            'search' => $_GET['search'] ?? null,
            'active_only' => ($_GET['active_only'] ?? '0') === '1'
        ];
        jsonResponse($mentorService->getMentors($filters));
        break;

    case 'POST':
        if (!$auth->isAuthenticated() || $auth->getUserType() !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
        }
        $payload = getRequestPayload();
        $result = $mentorService->registerMentor($payload, $auth->getUserId());
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
