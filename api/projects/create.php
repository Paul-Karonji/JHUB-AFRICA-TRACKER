<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

$project = new Project();
$payload = getRequestPayload();
$result = $project->createProject($payload);
jsonResponse($result, $result['success'] ? 201 : 400);
?>
