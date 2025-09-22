<?php
require_once __DIR__ . '/../../includes/init.php';
requireMethod('POST');

$auth = new Auth();

try {
    $payload = getRequestPayload();
    if (isset($payload['csrf_token']) && !$auth->verifyCSRFToken($payload['csrf_token'])) {
        jsonResponse([
            'success' => false,
            'error' => 'Invalid security token'
        ], 403);
    }

    $result = $auth->logout();
    jsonResponse($result);
} catch (Throwable $e) {
    logActivity('ERROR', 'API logout error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Logout failed'
    ], 500);
}
?>
