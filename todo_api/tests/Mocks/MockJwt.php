<?php

namespace Tests\Mocks;

/**
 * Mock JWT functions for testing
 */
class MockJwt
{
    /**
     * Generate a JWT token
     */
    public static function generateJWT($userId, $role = 'user')
    {
        // Just a simple mock token for testing
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'userId' => $userId,
            'role' => $role,
            'exp' => time() + 3600
        ]));
        $signature = base64_encode('mock_signature');
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Validate a JWT token
     */
    public static function validateJWT($token)
    {
        // Parse the token parts
        list($header, $payload, $signature) = explode('.', $token);
        
        // Decode the payload
        $decoded = json_decode(base64_decode($payload), true);
        
        // Check if token has expired
        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return false;
        }
        
        return $decoded;
    }
}