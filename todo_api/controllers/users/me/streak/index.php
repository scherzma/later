<?php
require_once "./inc/auth.php";
require_once "./Model/User.php";
require_once "./Model/Task.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Authenticate user
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ($method === 'GET') {
    // Get streak information using the updated model method
    echo json_encode([
        'streakInfo' => $user->getStreakInfo()
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>