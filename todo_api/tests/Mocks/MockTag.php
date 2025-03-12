<?php

namespace Tests\Mocks;

/**
 * A simplified mock of the Tag class for testing
 */
class MockTag
{
    private $tagId;
    private $name;
    private $priority = 'medium';
    private $userId;
    
    public function __construct($id = null)
    {
        if ($id) {
            $this->tagId = $id;
            $this->name = "Tag #{$id}";
            $this->userId = 1;
        }
    }
    
    public function loadFromData($data)
    {
        $this->tagId = $data['TagID'] ?? null;
        $this->name = $data['Name'] ?? '';
        $this->priority = $data['Priority'] ?? 'medium';
        $this->userId = $data['UserID'] ?? null;
    }
    
    // Getters
    public function getTagId() { return $this->tagId; }
    public function getName() { return $this->name; }
    public function getPriority() { return $this->priority; }
    public function getUserId() { return $this->userId; }
    
    // Setters
    public function setName($name, $autoSave = true) {
        $this->name = $name;
    }
    
    public function setPriority($priority, $autoSave = true) {
        if (in_array($priority, ['low', 'medium', 'high'])) {
            $this->priority = $priority;
        } else {
            $this->priority = 'medium';
        }
    }
    
    public function setUserId($userId, $autoSave = true) {
        $this->userId = $userId;
    }
}