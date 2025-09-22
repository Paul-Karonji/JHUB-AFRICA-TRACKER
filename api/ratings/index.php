<?php
require_once __DIR__ . '/../includes/init.php';

$ratingService = new Rating();
$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['project_id'])) {
            $projectId = $_GET['project_id'];
            if (($_GET['scope'] ?? '') === 'timeline') {
                jsonResponse($ratingService->getProjectTimeline($projectId));
            } elseif (($_GET['scope'] ?? '') === 'latest') {
                jsonResponse(['success' => true, 'rating' => $ratingService->getLatestRating($projectId)]);
            } else {
                jsonResponse($ratingService->getProjectRatings($projectId));
            }
        } elseif (isset($_GET['mentor_id'])) {
            jsonResponse($ratingService->getRatingsByMentor($_GET['mentor_id']));
        } else {
            jsonResponse(['success' => true, 'stats' => $ratingService->getSystemRatingStats()]);
        }
        break;

    case 'POST':
        if (!$auth->isAuthenticated() || $auth->getUserType() !== 'mentor') {
            jsonResponse(['success' => false, 'error' => 'Mentor authentication required'], 403);
        }
        $payload = getRequestPayload();
        requireKeys($payload, ['project_id', 'stage', 'percentage']);
        $result = $ratingService->updateProjectRating(
            $payload['project_id'],
            $auth->getUserId(),
            (int) $payload['stage'],
            (int) $payload['percentage'],
            $payload['notes'] ?? null
        );
        jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}
?>
