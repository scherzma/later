<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\Mocks\MockTag;

class TagIntegrationTest extends TestCase
{
    private $testToken;
    private $testTagId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a JWT for testing
        $this->testToken = 'test_token';
        
        // Create a test tag ID
        $this->testTagId = 1;
    }
    
    /**
     * Test creating a Tag
     */
    public function testTagCreation()
    {
        $tag = new MockTag();
        $tagName = 'TestTag_' . rand(1000, 9999);
        
        // Set tag properties
        $tag->setName($tagName, false);
        $tag->setPriority('medium', false);
        $tag->setUserId(1, false); // Assuming user ID 1 exists
        
        // Check tag properties were set
        $this->assertEquals($tagName, $tag->getName());
        $this->assertEquals('medium', $tag->getPriority());
        $this->assertEquals(1, $tag->getUserId());
    }
    
    /**
     * Test tag priority validation
     */
    public function testTagPriorityValidation()
    {
        $tag = new MockTag();
        
        // Valid priorities should work
        $tag->setPriority('low', false);
        $this->assertEquals('low', $tag->getPriority());
        
        $tag->setPriority('medium', false);
        $this->assertEquals('medium', $tag->getPriority());
        
        $tag->setPriority('high', false);
        $this->assertEquals('high', $tag->getPriority());
        
        // Invalid priority should default to medium
        $tag->setPriority('invalid', false);
        $this->assertEquals('medium', $tag->getPriority());
    }
    
    /**
     * Test loading a tag
     */
    public function testTagLoading()
    {
        // Create a new tag with an ID
        $tag = new MockTag($this->testTagId);
        
        // Check that the tag was loaded
        $this->assertEquals($this->testTagId, $tag->getTagId());
        $this->assertNotEmpty($tag->getName());
    }
}