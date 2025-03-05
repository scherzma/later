<?php
require_once "./inc/auth.php";
require_once "./inc/session.php";

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get session ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['sessionId'] ?? '';
    
    // Attempt to authenticate with JWT
    try {
        requireAuth(); // This will exit if authentication fails
        
        // If JWT authentication succeeds, end the session
        $success = endSession($sessionId);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(['message' => 'Logged out successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Session not found or already expired']);
        }
    } catch (Exception $e) {
        // If JWT auth fails but we have a session ID, try to end it anyway
        if (!empty($sessionId)) {
            endSession($sessionId);
        }
        
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'message' => 'Session expired or invalid']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>