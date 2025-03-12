<?php

namespace Tests\Mocks;

use InvalidArgumentException;

/**
 * A simplified mock of the Task class for testing
 */
class MockTask
{
    private $taskId;
    private $title;
    private $description;
    private $endDate;
    private $priority = 'medium';
    private $location;
    private $userId;
    private $locationId;
    private $finished = false;
    private $dateFinished = null;
    
    public function __construct($id = null)
    {
        if ($id) {
            $this->taskId = $id;
            $this->title = "Task #{$id}";
            $this->description = "Description for task #{$id}";
        }
    }
    
    // Getters
    public function getTaskId() { return $this->taskId; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getEndDate() { return $this->endDate; }
    public function getPriority() { return $this->priority; }
    public function getLocation() { return $this->location; }
    public function getUserId() { return $this->userId; }
    public function getLocationId() { return $this->locationId; }
    public function getFinished() { return $this->finished; }
    public function getDateFinished() { return $this->dateFinished; }
    
    // Setters
    public function setTitle($title, $autoSave = true) {
        $this->title = $title;
    }
    
    public function setDescription($description, $autoSave = true) {
        $this->description = $description;
    }
    
    public function setEndDate($endDate, $autoSave = true) {
        $this->endDate = $endDate;
    }
    
    public function setPriority($priority, $autoSave = true) {
        if (in_array($priority, ['low', 'medium', 'high'])) {
            $this->priority = $priority;
        } elseif ($priority === null) {
            $this->priority = 'medium';
        } else {
            throw new InvalidArgumentException("Priority must be 'none'(=medium), 'low', 'medium', or 'high'");
        }
    }
    
    public function setLocation($location, $autoSave = true) {
        $this->location = $location;
    }
    
    public function setUserId($userId, $autoSave = true) {
        $this->userId = $userId;
    }
    
    public function setLocationId($locationId, $autoSave = true) {
        $this->locationId = $locationId;
    }
    
    public function setFinished($finished, $autoSave = true) {
        $this->finished = (bool)$finished;
    }
    
    public function setDateFinished($dateFinished, $autoSave = true) {
        $this->dateFinished = $dateFinished;
    }
}