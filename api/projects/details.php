<?php
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$projectId = $_GET['project_id'] ?? null;
if (!$projectId) {
    jsonResponse(['success' => false, 'error' => 'project_id is required'], 400);
}

$includeTeam = ($_GET['include_team'] ?? '1') !== '0';
$includeMentors = ($_GET['include_mentors'] ?? '1') !== '0';

$project = new Project();
jsonResponse($project->getProject($projectId, $includeTeam, $includeMentors));
?>
