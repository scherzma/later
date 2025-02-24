<?php
require_once "./Model/todo_db.inc.php";

class Tag {
    private $db;
    private $tagId;
    private $name;
    private $priority;
    private $userId;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $query = "SELECT * FROM Tag WHERE TagID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen();
        if (!empty($data)) {
            $this->loadFromData($data[0]);
            return true;
        }
        return false;
    }

    public function loadFromData($data) {
        $this->tagId = $data['TagID'];
        $this->name = $data['Name'];
        $this->priority = $data['Priority'];
        $this->userId = $data['UserID'];
    }

    public function save() {
        if ($this->tagId === null) {
            $query = "INSERT INTO Tag (Name, Priority, UserID) VALUES (?, ?, ?)";
            $this->db->myQuery($query, [$this->name, $this->priority, $this->userId]);
            $this->tagId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE Tag SET Name = ?, Priority = ?, UserID = ? WHERE TagID = ?";
            $this->db->myQuery($query, [$this->name, $this->priority, $this->userId, $this->tagId]);
        }
    }

    public function delete() {
        if ($this->tagId !== null) {
            $query = "DELETE FROM Tag WHERE TagID = ?";
            $this->db->myQuery($query, [$this->tagId]);
            $this->tagId = null;
        }
    }

    // New method to get tasks associated with this tag
    public function getTasks() {
        $query = "SELECT t.* FROM Task t JOIN TaskTag tt ON t.TaskID = tt.TaskID WHERE tt.TagID = ? AND t.UserID = ?";
        $this->db->myQuery($query, [$this->tagId, $this->userId]);
        $tasksData = $this->db->gibZeilen();
        $tasks = [];
        foreach ($tasksData as $data) {
            $task = new Task($data['TaskID']); // Use constructor with ID
            $tasks[] = $task;
        }
        return $tasks;
    }

    public static function getTagsByUserId($userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM Tag WHERE UserID = ?";
        $db->myQuery($query, [$userId]);
        $tagsData = $db->gibZeilen();
        $tags = [];
        foreach ($tagsData as $data) {
            $tag = new Tag();
            $tag->loadFromData($data);
            $tags[] = $tag;
        }
        return $tags;
    }

    // Getters
    public function getTagId() { return $this->tagId; }
    public function getName() { return $this->name; }
    public function getPriority() { return $this->priority; }
    public function getUserId() { return $this->userId; }

    // Setters
    public function setName($name, $autoSave = true) {
        $this->name = $name;
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
    public function setUserId($userId, $autoSave = true) {
        $this->userId = $userId;
        if ($autoSave) {
            $this->save();
        }
    }
}
?>