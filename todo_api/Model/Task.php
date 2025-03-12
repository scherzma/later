<?php
require_once "./Model/todo_db.inc.php";
require_once "./Model/User.php";
require_once "./Model/Location.php";
require_once "./Model/Tag.php";
require_once "./Model/TaskReminder.php";

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
    private $finished;
    private $user = null;
    private $locationObj = null;
    private $everPostponed = false; // Flag to track if task was ever postponed
    private $dateFinished;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        // Lazy loading: Only fetch this specific task when needed
        $query = "SELECT * FROM Task WHERE TaskID = ?";
        $this->db->myQuery($query, [$id]);
        $result = $this->db->gibZeilen();

        if (empty($result)) {
            return false;
        }

        $data = $result[0];
        $this->loadFromData($data);
        return true;
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
        $this->finished = (bool)$data['Finished'];
        $this->dateFinished = $data['DateFinished']; // Add this line
    }

    public function save() {
        if ($this->taskId === null) {
            $query = "INSERT INTO Task (Title, Description, EndDate, Priority, Location, UserID, LocationID, Finished, DateFinished) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->myQuery($query, [$this->title, $this->description, $this->endDate, $this->priority, $this->location, $this->userId, $this->locationId, (int)$this->finished, $this->dateFinished]);
            $this->taskId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE Task SET Title = ?, Description = ?, EndDate = ?, Priority = ?, Location = ?, UserID = ?, LocationID = ?, Finished = ?, DateFinished = ? WHERE TaskID = ?";
            $this->db->myQuery($query, [$this->title, $this->description, $this->endDate, $this->priority, $this->location, $this->userId, $this->locationId, (int)$this->finished, $this->dateFinished, $this->taskId]);
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
    public function getFinished() { return $this->finished; }
    
    /**
     * Lazy Loading: User object is only loaded when explicitly requested
     * This prevents unnecessary database queries if the user data isn't needed
     */
    public function getUser() {
        if ($this->user === null && $this->userId !== null) {
            $this->user = new User($this->userId);
        }
        return $this->user;
    }
    
    /**
     * Lazy Loading: Location object is only loaded when explicitly requested
     * This prevents unnecessary database queries if the location data isn't needed
     */
    public function getLocationObj() {
        if ($this->locationObj === null && $this->locationId !== null) {
            $this->locationObj = new Location($this->locationId);
        }
        return $this->locationObj;
    }

    public static function getTasksByUserId($userId) {
        // Lazy loading: Only basic task data is loaded, related objects are not loaded
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM Task WHERE UserID = ?";
        $db->myQuery($query, [$userId]);
        $tasksData = $db->gibZeilen();
        $tasks = [];
        foreach ($tasksData as $data) {
            $task = new Task();
            $task->loadFromData($data);
            $tasks[] = $task;
        }
        return $tasks;
    }

    public static function getTaskByUserId($userId, $title) {
        // Lazy loading: Only basic task data is loaded, related objects are not loaded
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM Task WHERE UserID = ? AND Title = ?";
        $db->myQuery($query, [$userId, $title]);
        $data = $db->gibZeilen();

        if (empty($data)) {
            return null;
        }

        $task = new Task();
        $task->loadFromData($data[0]);
        return $task;
    }

    // Relationship Methods
    /**
     * Eager Loading: This fetches all tags for a task in a single query
     * Uses JOIN to retrieve all related tags at once
     */
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

    /**
     * Eager Loading: This fetches all reminders for a task in a single query
     */
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
    
    /**
     * Check if a task has ever been postponed
     * 
     * IMPORTANT: Due to how the database works, this will only track tasks
     * that are STILL in the queue. It cannot track tasks that were once
     * in the queue but have been removed.
     *
     * @return bool Whether the task is currently or has ever been in the queue
     */
    public function hasBeenPostponed() {
        if ($this->taskId === null) {
            return false;
        }
        
        // For this implementation, we'll create a more reliable way to track postponed tasks
        // We'll add a field to flag this task as "ever postponed"
        
        // First, just check if it's in the current queue
        $query = "SELECT COUNT(*) as count FROM TaskQueue WHERE TaskID = ?";
        $this->db->myQuery($query, [$this->taskId]);
        $result = $this->db->gibZeilen();
        $inQueue = (int)$result[0]['count'] > 0;
        
        error_log("Task {$this->taskId} postponement check - Currently in queue: " . ($inQueue ? 'Yes' : 'No'));
        
        // For now, we'll only use the current queue status
        // A more complete solution would require adding a "was_postponed" flag to the Task table
        return $inQueue;
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
        } elseif ($priority === null) {
            $this->priority = 'medium';
            if ($autoSave) {
                $this->save();
            }
        } else {
            throw new InvalidArgumentException("Priority must be 'none'(=medium), 'low', 'medium', or 'high'");
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
    public function setFinished($finished, $autoSave = true) {
        $this->finished = (bool)$finished;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setDateFinished($dateFinished, $autoSave = true) {
        $this->dateFinished = $dateFinished;
        if ($autoSave) {
            $this->save();
        }
    }

    public function getDateFinished() {
        return $this->dateFinished;
    }


}
?>