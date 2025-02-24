<?php
require_once "./todo_db.inc.php";

class Tag {
    private $db;
    private $tagId;
    private $name;
    private $priority;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $query = "SELECT * FROM Tag WHERE TagID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen()[0];
        $this->loadFromData($data);
    }

    public function loadFromData($data) {
        $this->tagId = $data['TagID'];
        $this->name = $data['Name'];
        $this->priority = $data['Priority'];
    }

    public function save() {
        if ($this->tagId === null) {
            $query = "INSERT INTO Tag (Name, Priority) VALUES (?, ?)";
            $this->db->myQuery($query, [$this->name, $this->priority]);
            $this->tagId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE Tag SET Name = ?, Priority = ? WHERE TagID = ?";
            $this->db->myQuery($query, [$this->name, $this->priority, $this->tagId]);
        }
    }

    public function delete() {
        if ($this->tagId !== null) {
            $query = "DELETE FROM Tag WHERE TagID = ?";
            $this->db->myQuery($query, [$this->tagId]);
            $this->tagId = null;
        }
    }

    // Getters
    public function getTagId() { return $this->tagId; }
    public function getName() { return $this->name; }
    public function getPriority() { return $this->priority; }

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
}
?>