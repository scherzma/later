<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $dbBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up environment variables
        $_ENV['JWT_SECRET'] = 'test-secret-key';
        
        // Include necessary files for testing
        if (!class_exists('Todo_DB')) {
            require_once __DIR__ . '/../Model/todo_db.inc.php';
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        parent::tearDown();
    }
    
    /**
     * Get a test database connection
     * This uses a separate configuration for testing to avoid affecting the production database
     */
    protected function getTestDb()
    {
        // This could be a memory SQLite DB or a test MySQL DB
        // For now, we'll just return the regular DB instance but in a real test
        // environment, you'd use a separate test database
        return \Todo_DB::gibInstanz();
    }
    
    /**
     * Create a HTTP request with given parameters
     */
    protected function createRequest(string $method, string $uri, array $data = [], array $headers = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        
        // Set headers
        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        
        // Set request body for POST/PUT requests
        if ($method === 'POST' || $method === 'PUT') {
            $_POST = $data;
            $_REQUEST = $data;
        }
        
        // Set query parameters for GET requests
        if ($method === 'GET') {
            $_GET = $data;
            $_REQUEST = $data;
        }
        
        return true;
    }
}