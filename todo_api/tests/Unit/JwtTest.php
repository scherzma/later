<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Mocks\MockJwt;

class JwtTest extends TestCase
{
    /**
     * Test JWT token generation
     */
    public function testGenerateJWT()
    {
        // Generate a token for user ID 1
        $userId = 1;
        $token = MockJwt::generateJWT($userId);
        
        // The token should be a non-empty string
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // The token should have 3 parts separated by dots
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts);
    }
    
    /**
     * Test JWT token validation
     */
    public function testValidateJWT()
    {
        // Generate a token
        $userId = 1;
        $token = MockJwt::generateJWT($userId);
        
        // Validate the token
        $payload = MockJwt::validateJWT($token);
        
        // Check that validation succeeded and returned the correct user ID
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('userId', $payload);
        $this->assertEquals($userId, $payload['userId']);
    }
    
    /**
     * Test JWT token expiration
     */
    public function testJWTExpiration()
    {
        // Generate a token
        $userId = 1;
        $token = MockJwt::generateJWT($userId);
        
        // Validate the token
        $payload = MockJwt::validateJWT($token);
        
        // Check that the token has an expiration time
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('exp', $payload);
        
        // The expiration should be in the future
        $this->assertGreaterThan(time(), $payload['exp']);
    }
}