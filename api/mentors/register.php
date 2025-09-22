<?php
require_once __DIR__ . '/../../includes/init.php';

$auth = new Auth();
if (!$auth->isAuthenticated() || $auth->getUserType() !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$mentorService = new Mentor();
$payload = getRequestPayload();
$result = $mentorService->registerMentor($payload, $auth->getUserId());
jsonResponse($result, $result['success'] ? 201 : 400);
?>
