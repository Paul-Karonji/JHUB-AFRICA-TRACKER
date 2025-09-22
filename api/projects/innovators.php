<?php
require_once __DIR__ . '/../../includes/init.php';

$project = new Project();
$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $projectId = $_GET['project_id'] ?? null;
        if (!$projectId) {
            jsonResponse(['success' => false, 'error' => 'project_id is required'], 400);
        }
        jsonResponse($project->getProjectTeam($projectId));
        break;

    case 'POST':
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $payload = getRequestPayload();
        requireKeys($payload, ['project_id', 'name', 'email', 'role']);
        $projectId = $payload['project_id'];
        $addedBy = $auth->getUserType() === 'project' ? $auth->getProjectId() : $auth->getUserId();
        $result = $project->addInnovator($projectId, $payload, $addedBy);
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    case 'DELETE':
        if (!$auth->isAuthenticated()) {
            jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
        }
        $projectId = $_GET['project_id'] ?? null;
        $innovatorId = $_GET['innovator_id'] ?? null;
        if (!$projectId || !$innovatorId) {
            jsonResponse(['success' => false, 'error' => 'project_id and innovator_id are required'], 400);
        }
        $removedBy = $auth->getUserId();
        $result = $project->removeInnovator($projectId, $innovatorId, $removedBy);
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
