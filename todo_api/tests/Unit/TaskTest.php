<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Mocks\MockTask;

class TaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    
    /**
     * Test that a task can be created with basic properties
     */
    public function testTaskCreation()
    {
        $task = new MockTask();
        
        // Set basic properties
        $task->setTitle('Test Task', false);
        $task->setDescription('This is a test task', false);
        $task->setPriority('medium', false);
        
        // Check that properties were set
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('This is a test task', $task->getDescription());
        $this->assertEquals('medium', $task->getPriority());
    }
    
    /**
     * Test task priority validation
     */
    public function testTaskPriorityValidation()
    {
        $task = new MockTask();
        
        // Valid priorities should work
        $task->setPriority('low', false);
        $this->assertEquals('low', $task->getPriority());
        
        $task->setPriority('medium', false);
        $this->assertEquals('medium', $task->getPriority());
        
        $task->setPriority('high', false);
        $this->assertEquals('high', $task->getPriority());
        
        // NULL should default to medium
        $task->setPriority(null, false);
        $this->assertEquals('medium', $task->getPriority());
        
        // Invalid priority should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $task->setPriority('invalid', false);
    }
    
    /**
     * Test task completion status
     */
    public function testTaskCompletionStatus()
    {
        $task = new MockTask();
        
        // Default should be false
        $this->assertFalse($task->getFinished());
        
        // Set to completed
        $task->setFinished(true, false);
        $this->assertTrue($task->getFinished());
        
        // Set back to incomplete
        $task->setFinished(false, false);
        $this->assertFalse($task->getFinished());
    }
    
    /**
     * Test task end date handling
     */
    public function testTaskEndDate()
    {
        $task = new MockTask();
        
        // Set an end date
        $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        $task->setEndDate($endDate, false);
        
        // Check it was set correctly
        $this->assertEquals($endDate, $task->getEndDate());
    }
}