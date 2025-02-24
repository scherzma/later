<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Generates a JWT for a given user ID.
 *
 * @param int $userId The ID of the user.
 * @return string The generated JWT.
 */
function generateJWT($userId) {
    $key = JWT_SECRET; // Defined in config.php
    $payload = [
        'iss' => 'splinter-labs.com', // Issuer
        'sub' => $userId,           // Subject (user ID)
        'iat' => time(),            // Issued at
        'exp' => time() + 36000,     // Expiration (10 hours)
    ];
    return JWT::encode($payload, $key, 'HS256');
}

/**
 * Verifies a JWT and returns the decoded payload.
 *
 * @param string $token The JWT to verify.
 * @return object|null The decoded payload or null if invalid.
 */
function verifyJWT($token) {
    $key = JWT_SECRET;
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}
?>