<?php
require_once __DIR__ . '/../../includes/init.php';

$projectService = new Project();
$mentorService = new Mentor();
$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $projectId = $_GET['project_id'] ?? null;
        if (!$projectId) {
            jsonResponse(['success' => false, 'error' => 'project_id is required'], 400);
        }
        jsonResponse($projectService->getProjectMentors($projectId));
        break;

    case 'POST':
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $payload = getRequestPayload();
        $projectId = $payload['project_id'] ?? null;
        if (!$projectId) {
            jsonResponse(['success' => false, 'error' => 'project_id is required'], 400);
        }
        $mentorId = $payload['mentor_id'] ?? $auth->getUserId();
        $selfAssigned = $auth->getUserType() === 'mentor' && $mentorId == $auth->getUserId();
        $result = $projectService->assignMentor($projectId, $mentorId, $selfAssigned);
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    case 'DELETE':
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $projectId = $_GET['project_id'] ?? null;
        $mentorId = $_GET['mentor_id'] ?? null;
        if (!$projectId || !$mentorId) {
            jsonResponse(['success' => false, 'error' => 'project_id and mentor_id are required'], 400);
        }
        if ($auth->getUserType() !== 'admin' && $auth->getUserId() != $mentorId) {
            jsonResponse(['success' => false, 'error' => 'Not authorized to remove mentor'], 403);
        }
        $result = $mentorService->removeFromProject($mentorId, $projectId);
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
