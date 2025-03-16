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


    public function testJwtGenerationAndValidation()
    {
        // 1. Setup - Import der JWT-Funktionen
        require_once dirname(dirname(__DIR__)) . '/inc/jwt.php';

        // 2. Testdaten
        $testUserId = 42;

        // 3. Testausführung - Token generieren
        $token = generateJWT($testUserId);

        // 4. Überprüfung - Token-Format
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Token besteht aus 3 durch Punkte getrennten Teilen (Header, Payload, Signature)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // 5. Testausführung - Token validieren
        $decoded = verifyJWT($token);

        // 6. Überprüfung der Validierung
        $this->assertIsObject($decoded);
        $this->assertEquals($testUserId, $decoded->sub); // 'sub' enthält die User ID

        // 7. Überprüfen des Ablaufdatums
        $this->assertObjectHasAttribute('exp', $decoded);
        $this->assertGreaterThan(time(), $decoded->exp);

        // 8. Überprüfen eines ungültigen Tokens
        $invalidToken = $token . 'tampered';
        $invalidDecoded = verifyJWT($invalidToken);
        $this->assertNull($invalidDecoded);
    }

}