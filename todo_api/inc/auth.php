<?php
require_once "./inc/jwt.php";

/**
 * Requires authentication for a request by checking the JWT.
 * Exits with 401 if authentication fails.
 *
 * @return int The authenticated user's ID.
 */
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? null;
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
    return $decoded->sub; // User ID from JWT payload
}



