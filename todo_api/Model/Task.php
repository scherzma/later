<?php
require_once "./todo_db.inc.php";
require_once "./User.php";
require_once "./Location.php";
require_once "./Tag.php";
require_once "./TaskReminder.php";

class Task {
    private $db;
    private $taskId;
    private $title;
    private $description;
    private $endDate;
    private $priority;
    private $location;
    private $userId;
    private $locationId;
    private $user = null;
    private $locationObj = null;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $query = "SELECT * FROM Task WHERE TaskID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen()[0];
        $this->loadFromData($data);
    }

    private function loadFromData($data) {
        $this->taskId = $data['TaskID'];
        $this->title = $data['Title'];
        $this->description = $data['Description'];
        $this->endDate = $data['EndDate'];
        $this->priority = $data['Priority'];
        $this->location = $data['Location'];
        $this->userId = $data['UserID'];
        $this->locationId = $data['LocationID'];
    }

    public function save() {
        if ($this->taskId === null) {
            $query = "INSERT INTO Task (Title, Description, EndDate, Priority, Location, UserID, LocationID) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->myQuery($query, [$this->title, $this->description, $this->endDate, $this->priority, $this->location, $this->userId, $this->locationId]);
            $this->taskId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE Task SET Title = ?, Description = ?, EndDate = ?, Priority = ?, Location = ?, UserID = ?, LocationID = ? WHERE TaskID = ?";
            $this->db->myQuery($query, [$this->title, $this->description, $this->endDate, $this->priority, $this->location, $this->userId, $this->locationId, $this->taskId]);
        }
    }

    public function delete() {
        if ($this->taskId !== null) {
            $query = "DELETE FROM Task WHERE TaskID = ?";
            $this->db->myQuery($query, [$this->taskId]);
            $this->taskId = null;
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
    public function getUser() {
        if ($this->user === null && $this->userId !== null) {
            $this->user = new User($this->userId);
        }
        return $this->user;
    }
    public function getLocationObj() {
        if ($this->locationObj === null && $this->locationId !== null) {
            $this->locationObj = new Location($this->locationId);
        }
        return $this->locationObj;
    }

    public static function getTasksByUserId($userId) {
        $db = Todo_DB::gibInstanz(); // Get the singleton database connection
        $query = "SELECT * FROM Task WHERE UserID = ?";
        $db->myQuery($query, [$userId]);
        $tasksData = $db->gibZeilen();
        $tasks = [];
        foreach ($tasksData as $data) {
            $task = new Task(); // Create a new Task instance
            $task->loadFromData($data); // Use existing loadFromData to populate it
            $tasks[] = $task;
        }
        return $tasks;
    }

    // Relationship Methods
    public function getTags() {
        $query = "SELECT t.* FROM Tag t JOIN TaskTag tt ON t.TagID = tt.TagID WHERE tt.TaskID = ?";
        $this->db->myQuery($query, [$this->taskId]);
        $tagsData = $this->db->gibZeilen();
        $tags = [];
        foreach ($tagsData as $data) {
            $tag = new Tag();
            $tag->loadFromData($data);
            $tags[] = $tag;
        }
        return $tags;
    }

    public function addTag($tagId) {
        $query = "INSERT INTO TaskTag (TaskID, TagID) VALUES (?, ?)";
        $this->db->myQuery($query, [$this->taskId, $tagId]);
    }

    public function removeTag($tagId) {
        $query = "DELETE FROM TaskTag WHERE TaskID = ? AND TagID = ?";
        $this->db->myQuery($query, [$this->taskId, $tagId]);
    }

    public function getReminders() {
        $query = "SELECT * FROM TaskReminder WHERE TaskID = ?";
        $this->db->myQuery($query, [$this->taskId]);
        $remindersData = $this->db->gibZeilen();
        $reminders = [];
        foreach ($remindersData as $data) {
            $reminder = new TaskReminder();
            $reminder->loadFromData($data);
            $reminders[] = $reminder;
        }
        return $reminders;
    }



    // Setters
    public function setTitle($title, $autoSave = true) {
        $this->title = $title;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setDescription($description, $autoSave = true) {
        $this->description = $description;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setEndDate($endDate, $autoSave = true) {
        $this->endDate = $endDate;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setPriority($priority, $autoSave = true) {
        if (in_array($priority, ['low', 'medium', 'high'])) {
            $this->priority = $priority;
            if ($autoSave) {
                $this->save();
            }
        } else {
            throw new InvalidArgumentException("Priority must be 'low', 'medium', or 'high'");
        }
    }
    public function setLocation($location, $autoSave = true) {
        $this->location = $location;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setUserId($userId, $autoSave = true) {
        $this->userId = $userId;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setLocationId($locationId, $autoSave = true) {
        $this->locationId = $locationId;
        if ($autoSave) {
            $this->save();
        }
    }
}
?>