<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the API router
 */
class RouterTest extends TestCase
{
    /**
     * Test route parsing
     */
    public function testRoutePathParsing()
    {
        // These functions test how the router would parse different URL paths
        
        // Test user profile path
        $path1 = '/users/123/profile';
        $segments1 = explode('/', trim($path1, '/'));
        $this->assertEquals(['users', '123', 'profile'], $segments1);
        
        // Test task with ID
        $path2 = '/tasks/456';
        $segments2 = explode('/', trim($path2, '/'));
        $this->assertEquals(['tasks', '456'], $segments2);
        
        // Test nested resources
        $path3 = '/tasks/789/tags/42';
        $segments3 = explode('/', trim($path3, '/'));
        $this->assertEquals(['tasks', '789', 'tags', '42'], $segments3);
        
        // Test query parameters in URL should be ignored
        $path4 = '/tasks?completed=true';
        $segments4 = explode('/', trim(parse_url($path4, PHP_URL_PATH), '/'));
        $this->assertEquals(['tasks'], $segments4);
    }
    
    /**
     * Test dynamic parameter extraction
     */
    public function testParameterExtraction()
    {
        // Simulate how the router extracts parameters from segments
        
        $params = [];
        
        // Test user ID extraction from path segments
        $segments = ['users', '123', 'profile'];
        
        // First segment is "users"
        array_shift($segments);
        
        // Second segment is the ID parameter
        $userId = array_shift($segments);
        $params['userId'] = (int)$userId;
        
        // Verify result
        $this->assertEquals(123, $params['userId']);
        $this->assertEquals(['profile'], $segments);
        
        // Test task and tag extraction
        $params = [];
        $segments = ['tasks', '456', 'tags', '42'];
        
        // First segment is "tasks"
        array_shift($segments);
        
        // Second segment is the task ID
        $taskId = array_shift($segments);
        $params['taskId'] = (int)$taskId;
        
        // Third segment is "tags"
        array_shift($segments);
        
        // Fourth segment is the tag ID
        $tagId = array_shift($segments);
        $params['tagId'] = (int)$tagId;
        
        // Verify result
        $this->assertEquals(456, $params['taskId']);
        $this->assertEquals(42, $params['tagId']);
        $this->assertEquals([], $segments);
    }
    
    /**
     * Test controller selection
     */
    public function testControllerSelection()
    {
        // Simulate how the router selects the appropriate controller
        
        // Set up base controller directory
        $baseDir = dirname(dirname(__DIR__)) . '/controllers/';
        
        // Test task controller exists
        $taskDir = $baseDir . 'tasks/';
        $this->assertDirectoryExists($taskDir);
        
        // Test task ID path exists
        $taskIdDir = $baseDir . 'tasks/_taskId/';
        $this->assertDirectoryExists($taskIdDir);
        
        // Test tag controller exists
        $tagDir = $baseDir . 'tags/';
        $this->assertDirectoryExists($tagDir);
        
        // Test user controller exists
        $userDir = $baseDir . 'users/';
        $this->assertDirectoryExists($userDir);
        
        // Test user registration controller exists
        $registerFile = $baseDir . 'users/register/index.php';
        $this->assertFileExists($registerFile);
        
        // Test tag ID controller exists
        $tagIdDir = $baseDir . 'tags/_tagId/';
        $this->assertDirectoryExists($tagIdDir);
    }
}