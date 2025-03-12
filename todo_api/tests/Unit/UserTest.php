<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Mocks\MockUser;

class UserTest extends TestCase
{
    private $testUsername;
    private $testPassword;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load the mock user class
        $this->testUsername = 'test_user_' . time() . rand(1000, 9999);
        $this->testPassword = 'TestPassword123!';
    }
    
    /**
     * Test that a new user can be created
     */
    public function testUserCanBeCreated()
    {
        $user = new MockUser();
        
        // Set user properties
        $user->setUsername($this->testUsername);
        $user->setPasswordHash(password_hash($this->testPassword, PASSWORD_DEFAULT));
        
        // Verify properties were set correctly
        $this->assertEquals($this->testUsername, $user->getUsername());
        $this->assertNotEmpty($user->getPasswordHash());
    }
    
    /**
     * Test password verification
     */
    public function testPasswordVerification()
    {
        $user = new MockUser();
        $plainPassword = 'test_password';
        
        // Set a password directly
        $user->setPasswordHash(password_hash($plainPassword, PASSWORD_DEFAULT));
        
        // Verify the password
        $this->assertTrue($user->verifyPassword($plainPassword));
        $this->assertFalse($user->verifyPassword('wrong_password'));
    }
    
    /**
     * Test user roles
     */
    public function testUserRoles()
    {
        $user = new MockUser();
        
        // Default role should be 'user'
        $this->assertEquals('user', $user->getRole());
        
        // Set role to admin
        $user->setRole('admin');
        $this->assertEquals('admin', $user->getRole());
        
        // Set back to user
        $user->setRole('user');
        $this->assertEquals('user', $user->getRole());
    }
}