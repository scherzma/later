<?php
require_once "./inc/auth.php";
require_once "./inc/session.php";
require_once "./Model/User.php";

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get session ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['sessionId'] ?? '';
    
    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID is required']);
        exit;
    }
    
    // Check if session is valid
    $isValid = checkSession($sessionId);
    
    if ($isValid) {
        // Get user ID from session
        $userId = User::validateSession($sessionId);
        $user = new User($userId);
        
        http_response_code(200);
        echo json_encode([
            'valid' => true,
            'user' => [
                'id' => $user->getUserId(),
                'username' => $user->getUsername(),
                'lastLogin' => $user->getLastLogin(),
                'lastActivity' => $user->getLastActivityTime()
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'error' => 'Session expired'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>