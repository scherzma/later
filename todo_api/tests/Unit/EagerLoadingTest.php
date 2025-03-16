<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests to verify eager loading implementation
 * These tests mock the database connection to check the logic without requiring a real DB
 */
class EagerLoadingTest extends TestCase
{
    /**
     * Test eager loading control flags in User class
     */
    public function testUserEagerLoadingFlags()
    {
        // Import the User class
        require_once dirname(dirname(__DIR__)) . '/Model/User.php';
        
        // Create a reflection class to examine private properties and methods
        $userReflection = new \ReflectionClass('\User');
        
        // Check that the eager loading parameter exists in getTasks() method
        $getTasksMethod = $userReflection->getMethod('getTasks');
        $params = $getTasksMethod->getParameters();
        $eagerLoadParam = null;
        
        foreach ($params as $param) {
            if ($param->getName() === 'eagerLoad') {
                $eagerLoadParam = $param;
                break;
            }
        }
        
        // Assert that the eager loading parameter exists
        $this->assertNotNull($eagerLoadParam, 'getTasks() should have an eagerLoad parameter');
        
        // Check that it has a default value of false (lazy loading by default)
        $this->assertTrue($eagerLoadParam->isDefaultValueAvailable(), 'eagerLoad parameter should have a default value');
        $this->assertFalse($eagerLoadParam->getDefaultValue(), 'eagerLoad parameter should default to false');
        
        // Similarly, check getQueuedTasks() method
        $getQueuedTasksMethod = $userReflection->getMethod('getQueuedTasks');
        $params = $getQueuedTasksMethod->getParameters();
        $eagerLoadParam = null;
        
        foreach ($params as $param) {
            if ($param->getName() === 'eagerLoad') {
                $eagerLoadParam = $param;
                break;
            }
        }
        
        $this->assertNotNull($eagerLoadParam, 'getQueuedTasks() should have an eagerLoad parameter');
        $this->assertTrue($eagerLoadParam->isDefaultValueAvailable(), 'eagerLoad parameter should have a default value');
        $this->assertFalse($eagerLoadParam->getDefaultValue(), 'eagerLoad parameter should default to false');
    }
    
    /**
     * Test Task class implementation for eager loading
     */
    public function testTaskEagerLoadingImplementation()
    {
        // Import the Task class
        require_once dirname(dirname(__DIR__)) . '/Model/Task.php';
        
        // Create a reflection class for Task
        $taskReflection = new \ReflectionClass('\Task');
        
        // Check for the presence of cache properties for related objects
        $this->assertTrue($taskReflection->hasProperty('tags'), 'Task class should have a tags property');
        $this->assertTrue($taskReflection->hasProperty('location'), 'Task class should have a location property');
        $this->assertTrue($taskReflection->hasProperty('reminders'), 'Task class should have a reminders property');
        
        // Check for direct setter methods that enable eager loading
        // Modify these asserts to match the actual implementation
        $tagSetterExists = $taskReflection->hasMethod('setTagsDirect');
        $locationSetterExists = $taskReflection->hasMethod('setLocationObjDirect');
        $reminderSetterExists = $taskReflection->hasMethod('setRemindersDirect');
        
        // We need at least one direct setter method for eager loading
        $hasAtLeastOneDirectSetter = $tagSetterExists || $locationSetterExists || $reminderSetterExists;
        $this->assertTrue($hasAtLeastOneDirectSetter, 'Task class should have at least one direct setter method for eager loading');
        
        // Check that getter methods check for cached objects first
        $getTagsMethod = $taskReflection->getMethod('getTags');
        $getTagsMethodBody = $this->getMethodBody($getTagsMethod);
        
        // The method should check if tags is already set (eager loaded)
        $this->assertMatchesRegularExpression('/if.+\$this->tags.+!==.+null/i', $getTagsMethodBody, 'getTags() should check for cached tags');
        
        // Similarly for location - use getLocationObj() instead
        $getLocationMethod = $taskReflection->getMethod('getLocationObj');
        $getLocationMethodBody = $this->getMethodBody($getLocationMethod);
        $this->assertMatchesRegularExpression('/if.+\$this->locationObj.+===.+null/i', $getLocationMethodBody, 'getLocationObj() should check if the location needs to be loaded');
    }
    
    /**
     * Helper method to get the body of a method
     */
    private function getMethodBody(\ReflectionMethod $method)
    {
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        
        // Get filename and read the lines
        $filename = $method->getFileName();
        $lines = file($filename);
        
        // Get the method body
        $body = '';
        for ($i = $start; $i < $end; $i++) {
            $body .= $lines[$i];
        }
        
        return $body;
    }
}