<?php
require_once "./inc/jwt.php";
require_once "./inc/session.php";
require_once "./Model/User.php";

/**
 * Requires authentication for a request by checking the JWT.
 * Also verifies the session if a session ID is provided.
 * Exits with 401 if authentication fails.
 *
 * @return int The authenticated user's ID.
 */
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? null;
    
    // Check for session ID in headers
    $sessionHeader = $headers['X-Session-ID'] ?? null;
    
    // First verify JWT token
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $token = $matches[1];
    $decoded = verifyJWT($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    $userId = $decoded->sub; // User ID from JWT payload
    
    // If session ID is provided, verify session activity
    if ($sessionHeader) {
        // Check if session is valid
        if (!checkSession($sessionHeader)) {
            // Session has expired, force logout
            http_response_code(401);
            echo json_encode([
                'error' => 'Session expired', 
                'code' => 'SESSION_EXPIRED'
            ]);
            exit;
        }
        
        // Verify the session belongs to the authenticated user
        $sessionUserId = User::validateSession($sessionHeader);
        if ($sessionUserId !== $userId) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Session mismatch', 
                'code' => 'SESSION_USER_MISMATCH'
            ]);
            exit;
        }
    }
    
    // Update user's activity timestamp
    $user = new User($userId);
    $user->updateActivity();
    
    return $userId;
}