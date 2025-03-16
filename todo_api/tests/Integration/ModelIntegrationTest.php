<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Integration tests for the model classes
 * These tests verify that the models interact properly with each other and the database
 */
class ModelIntegrationTest extends TestCase
{
    /**
     * Test user registration and authentication
     */
    public function testUserRegistrationAndAuth()
    {
        // Import the User class
        require_once dirname(dirname(__DIR__)) . '/Model/User.php';
        
        // Generate a unique username
        $username = 'testuser_' . time();
        $password = 'test_password123';
        
        // Create a new user directly
        $user = new \User();
        $user->setUsername($username, false);
        $user->setPassword($password, false);
        $result = $user->save();
        
        // Assert that user creation was successful
        $this->assertTrue($result > 0);
        
        // Try to find the user
        $foundUser = \User::findByUsername($username);
        $this->assertNotNull($foundUser);
        $this->assertEquals($username, $foundUser->getUsername());
        
        // Verify password hash
        $this->assertTrue(password_verify($password, $foundUser->getPasswordHash()));
        
        // Test failed login
        $this->assertFalse(password_verify('wrong_password', $foundUser->getPasswordHash()));
    }
    
    /**
     * Test task creation and management
     */
    public function testTaskManagement()
    {
        // Import required classes
        require_once dirname(dirname(__DIR__)) . '/Model/User.php';
        require_once dirname(dirname(__DIR__)) . '/Model/Task.php';
        
        // Create a test user
        $username = 'tasktest_' . time();
        $user = new \User();
        $user->setUsername($username, false);
        $user->setPassword('task_password', false);
        $user->save();
        
        // Create a task
        $task = new \Task();
        $task->setTitle('Test Task');
        $task->setDescription('Test Description');
        $task->setPriority('medium');
        $task->setCreatedByUser($user);
        $taskId = $task->save();
        
        // Check that task creation was successful
        $this->assertTrue($taskId > 0);
        
        // Retrieve the task and check its properties
        $fetchedTask = \Task::findById($taskId);
        $this->assertNotNull($fetchedTask);
        $this->assertEquals('Test Task', $fetchedTask->getTitle());
        $this->assertEquals('Test Description', $fetchedTask->getDescription());
        $this->assertEquals('medium', $fetchedTask->getPriority());
        
        // Check that the user owns the task
        $userTasks = $user->getTasks();
        $this->assertNotEmpty($userTasks);
        $this->assertEquals($taskId, $userTasks[0]->getTaskId());
        
        // Update the task
        $fetchedTask->setTitle('Updated Task');
        $fetchedTask->setPriority('high');
        $fetchedTask->save();
        
        // Check that the update worked
        $updatedTask = \Task::findById($taskId);
        $this->assertEquals('Updated Task', $updatedTask->getTitle());
        $this->assertEquals('high', $updatedTask->getPriority());
        
        // Mark the task as complete
        $updatedTask->setCompleted(true);
        $updatedTask->save();
        
        // Verify it's marked as complete
        $completedTask = \Task::findById($taskId);
        $this->assertTrue($completedTask->isCompleted());
    }
    
    /**
     * Test task tagging functionality
     */
    public function testTaskTags()
    {
        // Import required classes
        require_once dirname(dirname(__DIR__)) . '/Model/User.php';
        require_once dirname(dirname(__DIR__)) . '/Model/Task.php';
        require_once dirname(dirname(__DIR__)) . '/Model/Tag.php';
        
        // Create a test user
        $username = 'tagstest_' . time();
        $user = new \User();
        $user->setUsername($username, false);
        $user->setPassword('tag_password', false);
        $user->save();
        
        // Create a task
        $task = new \Task();
        $task->setTitle('Tag Test Task');
        $task->setDescription('Testing tags');
        $task->setPriority('high');
        $task->setCreatedByUser($user);
        $taskId = $task->save();
        
        // Create tags and add them to the task
        $tag1 = new \Tag();
        $tag1->setName('test-tag');
        $tag1->setCreatedByUser($user);
        $tag1Id = $tag1->save();
        
        $tag2 = new \Tag();
        $tag2->setName('important');
        $tag2->setCreatedByUser($user);
        $tag2Id = $tag2->save();
        
        // Add tags to task
        $task->addTag($tag1);
        $task->addTag($tag2);
        
        // Fetch task tags
        $taskTags = $task->getTags();
        $this->assertCount(2, $taskTags);
        
        // Check tag names
        $tagNames = array_map(function($tag) {
            return $tag->getName();
        }, $taskTags);
        
        $this->assertContains('test-tag', $tagNames);
        $this->assertContains('important', $tagNames);
        
        // Get tasks by tag
        $taggedTasks = $tag1->getTasks();
        $this->assertCount(1, $taggedTasks);
        $this->assertEquals($taskId, $taggedTasks[0]->getTaskId());
        
        // Test removing a tag
        $task->removeTag($tag1);
        $updatedTags = $task->getTags();
        $this->assertCount(1, $updatedTags);
        $this->assertEquals('important', $updatedTags[0]->getName());
        
        // Verify tag was removed from task
        $updatedTaggedTasks = $tag1->getTasks();
        $this->assertEmpty($updatedTaggedTasks);
    }
    
    /**
     * Test reminders functionality
     */
    public function testTaskReminders()
    {
        // Import required classes
        require_once dirname(dirname(__DIR__)) . '/Model/User.php';
        require_once dirname(dirname(__DIR__)) . '/Model/Task.php';
        require_once dirname(dirname(__DIR__)) . '/Model/TaskReminder.php';
        
        // Create a test user
        $username = 'remindertest_' . time();
        $user = new \User();
        $user->setUsername($username, false);
        $user->setPassword('reminder_password', false);
        $user->save();
        
        // Create a task
        $task = new \Task();
        $task->setTitle('Reminder Test Task');
        $task->setDescription('Testing reminders');
        $task->setPriority('medium');
        $task->setCreatedByUser($user);
        $taskId = $task->save();
        
        // Add a reminder to the task
        $reminderTime = new \DateTime('+1 day');
        
        $reminder = new \TaskReminder();
        $reminder->setTask($task);
        $reminder->setReminderTime($reminderTime);
        $reminderId = $reminder->save();
        
        // Check that reminder creation was successful
        $this->assertTrue($reminderId > 0);
        
        // Retrieve the reminder
        $fetchedReminder = \TaskReminder::findById($reminderId);
        $this->assertNotNull($fetchedReminder);
        
        // Check that the reminder is linked to the correct task
        $reminderTask = $fetchedReminder->getTask();
        $this->assertEquals($taskId, $reminderTask->getTaskId());
        
        // Check that the task has the reminder
        $taskReminders = $task->getReminders();
        $this->assertCount(1, $taskReminders);
        $this->assertEquals($reminderId, $taskReminders[0]->getReminderId());
        
        // Update the reminder
        $newReminderTime = new \DateTime('+2 days');
        $fetchedReminder->setReminderTime($newReminderTime);
        $fetchedReminder->save();
        
        // Check that the update worked
        $updatedReminder = \TaskReminder::findById($reminderId);
        $this->assertEquals(
            $newReminderTime->format('Y-m-d H:i:s'),
            $updatedReminder->getReminderTime()->format('Y-m-d H:i:s')
        );
    }
}