<?php
require_once __DIR__ . '/../../includes/init.php';

$project = new Project();
$auth = new Auth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filters = [
            'status' => $_GET['status'] ?? null,
            'stage' => $_GET['stage'] ?? null,
            'search' => $_GET['search'] ?? null,
            'page' => $_GET['page'] ?? 1,
            'per_page' => $_GET['per_page'] ?? AppConfig::PROJECTS_PER_PAGE
        ];
        jsonResponse($project->getAllProjects($filters));
        break;

    case 'POST':
        $payload = getRequestPayload();
        $result = $project->createProject($payload);
        jsonResponse($result, $result['success'] ? 201 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>


