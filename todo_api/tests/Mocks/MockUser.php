<?php

namespace Tests\Mocks;

/**
 * A simplified mock of the User class for testing
 */
class MockUser
{
    private $userId = null;
    private $username = '';
    private $passwordHash = '';
    private $role = 'user';
    
    public function __construct($id = null)
    {
        if ($id) {
            $this->userId = $id;
            $this->username = "user_{$id}";
            $this->passwordHash = password_hash("password_{$id}", PASSWORD_DEFAULT);
        }
    }
    
    // Getters
    public function getUserId() { return $this->userId; }
    public function getUsername() { return $this->username; }
    public function getPasswordHash() { return $this->passwordHash; }
    public function getRole() { return $this->role; }
    
    // Setters
    public function setUsername($username) { $this->username = $username; }
    public function setPasswordHash($hash) { $this->passwordHash = $hash; }
    public function setRole($role) { $this->role = $role; }
    
    /**
     * Verify if a password matches the stored hash
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->passwordHash);
    }
}