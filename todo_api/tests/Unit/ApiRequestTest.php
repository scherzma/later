<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Utils\ApiUtils;

/**
 * Unit tests for API request handling
 */
class ApiRequestTest extends TestCase
{
    /**
     * Test request sanitization
     */
    public function testRequestSanitization()
    {
        // Test filtering input arrays
        $input = [
            'name' => 'John <script>alert("XSS")</script>',
            'email' => 'john@example.com',
            'nested' => [
                'key' => '<b>Bold</b> text',
                '<script>' => 'value'
            ]
        ];
        
        $filtered = ApiUtils::filterInput($input);
        
        // Check that XSS is properly filtered
        $this->assertStringNotContainsString('<script>', $filtered['name']);
        $this->assertStringContainsString('&lt;script&gt;', $filtered['name']);
        
        // Check nested arrays
        $this->assertStringNotContainsString('<b>', $filtered['nested']['key']);
        $this->assertStringContainsString('&lt;b&gt;', $filtered['nested']['key']);
        
        // Check that keys are also sanitized
        $this->assertArrayHasKey('&lt;script&gt;', $filtered['nested']);
    }
    
    /**
     * Test URI part sanitization
     */
    public function testUriPartSanitization()
    {
        // Test with unsafe URI parts
        $uriParts = [
            'users',
            '../config', // Path traversal attempt
            'user.<script>alert("XSS")</script>', // XSS attempt
            'task-1',
            '.htaccess' // Apache config file access attempt
        ];
        
        $sanitized = ApiUtils::sanitizeUriParts($uriParts);
        
        // Check that path traversal is prevented
        $this->assertNotContains('../config', $sanitized);
        
        // Check that XSS is prevented
        $this->assertNotContains('user.<script>alert("XSS")</script>', $sanitized);
        
        // Check that valid parts remain
        $this->assertContains('users', $sanitized);
        $this->assertContains('task-1', $sanitized);
        
        // Check that dot files are sanitized
        $this->assertNotContains('.htaccess', $sanitized);
    }
    
    /**
     * Test parameter sanitization
     */
    public function testParamSanitization()
    {
        // Test with different parameter types
        $userId = ApiUtils::sanitizeParam('userId', '123');
        $this->assertIsInt($userId);
        $this->assertEquals(123, $userId);
        
        $email = ApiUtils::sanitizeParam('email', 'test@example.com');
        $this->assertEquals('test@example.com', $email);
        
        $username = ApiUtils::sanitizeParam('username', 'test_user123');
        $this->assertEquals('test_user123', $username);
        
        // Test with invalid inputs
        $xssAttempt = ApiUtils::sanitizeParam('description', '<script>alert("XSS")</script>');
        $this->assertStringNotContainsString('<script>', $xssAttempt);
        $this->assertStringContainsString('&lt;script&gt;', $xssAttempt);
        
        // Test with invalid username characters
        $invalidUsername = ApiUtils::sanitizeParam('username', 'user<>name');
        $this->assertEquals('username', $invalidUsername);
    }
}