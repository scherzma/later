<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\PhpStreamMock;
use Tests\HttpMock;
use Tests\ApiExecutor;

abstract class TestCase extends BaseTestCase
{
    /**
     * API executor instance
     */
    protected $apiExecutor;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up environment variables for testing
        $_ENV['JWT_SECRET'] = 'test-secret-key';
        
        // Override PHP's http_response_code and header functions with our mocks
        $this->mockHttpFunctions();
        
        // Initialize the API executor
        $this->apiExecutor = new ApiExecutor();
        
        // Include necessary bootstrap files
        $this->includeBootstrap();
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up global state
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER = [];
        
        // Reset the HTTP mock
        HttpMock::reset();
    }
    
    /**
     * Get a test database connection
     */
    protected function getTestDb()
    {
        return \Todo_DB::gibInstanz();
    }
    
    /**
     * Call the API
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint (e.g., /users/login)
     * @param array $data Request data
     * @param array $headers Request headers
     * @return array Response data
     */
    protected function callApi(string $method, string $endpoint, array $data = [], array $headers = [])
    {
        return $this->apiExecutor->executeRequest($method, $endpoint, $data, $headers);
    }
    
    /**
     * Mock PHP's HTTP functions
     */
    private function mockHttpFunctions()
    {
        // Define function overrides in the global namespace
        if (!function_exists('http_response_code')) {
            function http_response_code($code = null) {
                return \Tests\HttpMock::responseCode($code);
            }
        }
        
        if (!function_exists('header')) {
            function header($header, $replace = true, $http_response_code = null) {
                return \Tests\HttpMock::header($header, $replace, $http_response_code);
            }
        }
    }
    
    /**
     * Include bootstrap functionality
     */
    private function includeBootstrap()
    {
        static $bootstrapIncluded = false;
        
        if (!$bootstrapIncluded) {
            // Define database configuration
            if (!defined('DB_HOST')) define('DB_HOST', 'db');
            if (!defined('DB_NAME')) define('DB_NAME', 'todo_db');
            if (!defined('DB_USER')) define('DB_USER', 'todo_user');
            if (!defined('DB_PASS')) define('DB_PASS', 'todo_password');
            
            // Define security settings
            if (!defined('JWT_SECRET')) define('JWT_SECRET', 'test_secret_key_for_jwt_tokens');
            if (!defined('BCRYPT_COST')) define('BCRYPT_COST', 4); // Lower cost for faster tests
            
            // Define application root
            if (!defined('APP_ROOT')) {
                define('APP_ROOT', dirname(__DIR__));
            }
            
            // Load autoloader
            require_once dirname(__DIR__) . '/vendor/autoload.php';
            
            // Load database class
            require_once dirname(__DIR__) . '/Model/todo_db.inc.php';
            
            $bootstrapIncluded = true;
        }
    }
}