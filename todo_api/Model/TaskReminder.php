<?php
require_once "./todo_db.inc.php";
require_once "./Task.php";

class TaskReminder {
    private $db;
    private $reminderId;
    private $taskId;
    private $reminderTime;
    private $isSent;
    private $task = null;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $query = "SELECT * FROM TaskReminder WHERE ReminderID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen()[0];
        $this->loadFromData($data);
    }

    public function loadFromData($data) {
        $this->reminderId = $data['ReminderID'];
        $this->taskId = $data['TaskID'];
        $this->reminderTime = $data['ReminderTime'];
        $this->isSent = $data['IsSent'];
    }

    public function save() {
        if ($this->reminderId === null) {
            $query = "INSERT INTO TaskReminder (TaskID, ReminderTime, IsSent) VALUES (?, ?, ?)";
            $this->db->myQuery($query, [$this->taskId, $this->reminderTime, $this->isSent]);
            $this->reminderId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE TaskReminder SET TaskID = ?, ReminderTime = ?, IsSent = ? WHERE ReminderID = ?";
            $this->db->myQuery($query, [$this->taskId, $this->reminderTime, $this->isSent, $this->reminderId]);
        }
    }

    public function delete() {
        if ($this->reminderId !== null) {
            $query = "DELETE FROM TaskReminder WHERE ReminderID = ?";
            $this->db->myQuery($query, [$this->reminderId]);
            $this->reminderId = null;
        }
    }

    // Getters
    public function getReminderId() { return $this->reminderId; }
    public function getTaskId() { return $this->taskId; }
    public function getReminderTime() { return $this->reminderTime; }
    public function getIsSent() { return $this->isSent; }
    public function getTask() {
        if ($this->task === null && $this->taskId !== null) {
            $this->task = new Task($this->taskId);
        }
        return $this->task;
    }

    // Setters
    public function setTaskId($taskId, $autoSave = true) {
        $this->taskId = $taskId;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setReminderTime($reminderTime, $autoSave = true) {
        $this->reminderTime = $reminderTime;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setIsSent($isSent, $autoSave = true) {
        $this->isSent = (bool)$isSent;
        if ($autoSave) {
            $this->save();
        }
    }
}
?>