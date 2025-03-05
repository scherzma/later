<?php
require_once "./Model/User.php";
require_once "./inc/jwt.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;
    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }
    
    // Start timing for consistent execution time (mitigate timing attacks)
    $startTime = microtime(true);
    
    // Find user by username
    $user = User::findByUsername($username);
    
    // Default response for invalid credentials
    $response = [
        'error' => 'Invalid credentials'
    ];
    $statusCode = 401;
    
    if ($user) {
        // Verify password
        if (password_verify($password, $user->getPasswordHash())) {
            // Check if password needs rehashing (using optimal cost factor)
            $user->updatePasswordHashIfNeeded($password);
            
            // Record successful login and update activity
            $user->recordSuccessfulLogin();
            
            // Create session for inactivity tracking
            $sessionId = $user->createSession(1800); // 30 minutes inactivity timeout
            
            // Generate JWT token for authentication
            $token = generateJWT($user->getUserId());
            
            // Include login info in response
            $response = [
                'token' => $token,
                'sessionId' => $sessionId,
                'user' => [
                    'id' => $user->getUserId(),
                    'username' => $user->getUsername(),
                    'lastLogin' => $user->getLastLogin(),
                    'failedAttempts' => $user->getFailedAttempts()
                ]
            ];
            $statusCode = 200;
        } else {
            // Record failed login attempt
            User::recordFailedLogin($username);
        }
    } else {
        // For non-existent users, do a dummy password verify to maintain consistent timing
        password_verify($password, '$2y$10$GlA.v9Z1R1xbr0aVYQFAGuHBZd6BIEVFKlxAzxK6QpgXdbQNj8zGe');
    }
    
    // Ensure consistent response time to prevent timing attacks
    $elapsedTime = microtime(true) - $startTime;
    $targetTime = 0.5; // 500ms consistent response time
    
    if ($elapsedTime < $targetTime) {
        usleep(($targetTime - $elapsedTime) * 1000000);
    }
    
    http_response_code($statusCode);
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>