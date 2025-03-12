<?php
require_once "./Model/todo_db.inc.php";
require_once "./Model/Task.php";
require_once "./Model/User.php";

class TaskQueue {
    private $db;
    private $queueId;
    private $taskId;
    private $userId;
    private $postponedDate;
    private $queuePosition;
    private $task = null;
    private $user = null;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        // Lazy loading: Only fetch this specific queue item when needed
        $query = "SELECT * FROM TaskQueue WHERE QueueID = ?";
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
        $this->queueId = $data['QueueID'];
        $this->taskId = $data['TaskID'];
        $this->userId = $data['UserID'];
        $this->postponedDate = $data['PostponedDate'];
        $this->queuePosition = $data['QueuePosition'];
    }

    public function save() {
        if ($this->queueId === null) {
            $query = "INSERT INTO TaskQueue (TaskID, UserID, PostponedDate, QueuePosition) VALUES (?, ?, ?, ?)";
            $this->db->myQuery($query, [
                $this->taskId,
                $this->userId,
                $this->postponedDate ?: date('Y-m-d H:i:s'),
                $this->queuePosition
            ]);
            $this->queueId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE TaskQueue SET TaskID = ?, UserID = ?, PostponedDate = ?, QueuePosition = ? WHERE QueueID = ?";
            $this->db->myQuery($query, [
                $this->taskId,
                $this->userId,
                $this->postponedDate,
                $this->queuePosition,
                $this->queueId
            ]);
        }
    }

    public function delete() {
        if ($this->queueId !== null) {
            $query = "DELETE FROM TaskQueue WHERE QueueID = ?";
            $this->db->myQuery($query, [$this->queueId]);
            $this->queueId = null;
        }
    }

    /**
     * Check if a task is in the queue for a specific user
     * Lazy loading approach: Only checks for existence without loading complete objects
     *
     * @param int $taskId Task ID
     * @param int $userId User ID
     * @return bool True if task is in queue
     */
    public static function isTaskInQueue($taskId, $userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT COUNT(*) as count FROM TaskQueue WHERE TaskID = ? AND UserID = ?";
        $db->myQuery($query, [$taskId, $userId]);
        $result = $db->gibZeilen();

        $count = (int)$result[0]['count'];
        error_log("Task {$taskId} for user {$userId} queue check - Count in queue: {$count}");
        
        return $count > 0;
    }

    /**
     * Get task position in queue
     * Lazy loading approach: Only fetches queue data without loading related objects
     *
     * @param int $taskId Task ID
     * @param int $userId User ID
     * @return array|null Queue info or null if not in queue
     */
    public static function getTaskQueueInfo($taskId, $userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM TaskQueue WHERE TaskID = ? AND UserID = ?";
        $db->myQuery($query, [$taskId, $userId]);
        $result = $db->gibZeilen();

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    /**
     * Get the maximum queue position for a user
     *
     * @param int $userId User ID
     * @return int The max position (0 if queue is empty)
     */
    private static function getMaxQueuePosition($userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT MAX(QueuePosition) as maxPos FROM TaskQueue WHERE UserID = ?";
        $db->myQuery($query, [$userId]);
        $result = $db->gibZeilen();

        return isset($result[0]['maxPos']) ? (int)$result[0]['maxPos'] : 0;
    }

    /**
     * Count items in user's queue
     *
     * @param int $userId User ID
     * @return int Number of queued items
     */
    public static function countQueueItems($userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT COUNT(*) as count FROM TaskQueue WHERE UserID = ?";
        $db->myQuery($query, [$userId]);
        $result = $db->gibZeilen();

        return (int)$result[0]['count'];
    }

    /**
     * Add a task to the user's queue
     *
     * @param int $taskId The task ID to add
     * @param int $userId The user ID
     * @return TaskQueue The created queue item
     */
    public static function addTaskToQueue($taskId, $userId) {
        // Find the highest current position for this user
        $maxPosition = self::getMaxQueuePosition($userId);

        // Create a new queue item with next position
        $queueItem = new TaskQueue();
        $queueItem->setTaskId($taskId, false);
        $queueItem->setUserId($userId, false);
        $queueItem->setPostponedDate(date('Y-m-d H:i:s'), false);
        $queueItem->setQueuePosition($maxPosition + 1, false);
        $queueItem->save();

        return $queueItem;
    }

    /**
     * Get the next task in the queue for a user
     * 
     * Eager Loading: This joins the Task and TaskQueue tables to get the next task
     * in a single query rather than first getting the queue item and then loading the task
     *
     * @param int $userId The user ID
     * @return Task|null The next task or null if queue is empty
     */
    public static function getNextTaskInQueue($userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT t.TaskID FROM Task t 
                  JOIN TaskQueue tq ON t.TaskID = tq.TaskID 
                  WHERE tq.UserID = ? AND t.Finished = 0 
                  ORDER BY tq.QueuePosition ASC 
                  LIMIT 1";
        $db->myQuery($query, [$userId]);
        $result = $db->gibZeilen();

        if (empty($result)) {
            return null;
        }

        return new Task($result[0]['TaskID']);
    }

    /**
     * Remove a task from the queue
     *
     * @param int $taskId The task ID to remove
     * @param int $userId The user ID
     * @return boolean Success
     */
    public static function removeTaskFromQueue($taskId, $userId) {
        $db = Todo_DB::gibInstanz();

        // Get the current position
        $queueInfo = self::getTaskQueueInfo($taskId, $userId);

        if (!$queueInfo) {
            return false;
        }

        $currentPosition = $queueInfo['QueuePosition'];

        // Delete the queue item
        $query = "DELETE FROM TaskQueue WHERE TaskID = ? AND UserID = ?";
        $db->myQuery($query, [$taskId, $userId]);

        // Update positions for items after the deleted one
        $query = "UPDATE TaskQueue SET QueuePosition = QueuePosition - 1 
                  WHERE UserID = ? AND QueuePosition > ?";
        $db->myQuery($query, [$userId, $currentPosition]);

        return true;
    }

    /**
     * Get all queued tasks for a user
     * 
     * Eager Loading: This joins the Task and TaskQueue tables to get all tasks
     * in a single query rather than first getting queue items and then loading tasks
     *
     * @param int $userId The user ID
     * @return array Array of Task objects
     */
    public static function getQueuedTasksByUser($userId) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT t.TaskID FROM Task t 
                  JOIN TaskQueue tq ON t.TaskID = tq.TaskID 
                  WHERE tq.UserID = ? 
                  ORDER BY tq.QueuePosition ASC";
        $db->myQuery($query, [$userId]);
        $tasksData = $db->gibZeilen();

        $tasks = [];
        foreach ($tasksData as $data) {
            $tasks[] = new Task($data['TaskID']);
        }

        return $tasks;
    }

    /**
     * Reorder a task in the queue
     *
     * @param int $taskId The task ID to reorder
     * @param int $userId The user ID
     * @param int $newPosition The new position
     * @return boolean Success
     */
    public static function reorderTask($taskId, $userId, $newPosition) {
        $db = Todo_DB::gibInstanz();

        // Get the current position
        $queueInfo = self::getTaskQueueInfo($taskId, $userId);

        if (!$queueInfo) {
            return false;
        }

        $currentPosition = $queueInfo['QueuePosition'];
        $totalItems = self::countQueueItems($userId);

        // Validate new position
        if ($newPosition < 1 || $newPosition > $totalItems) {
            return false;
        }

        // No change needed
        if ($newPosition == $currentPosition) {
            return true;
        }

        // Update positions for affected items
        if ($newPosition < $currentPosition) {
            // Moving up in queue - shift other items down
            $query = "UPDATE TaskQueue SET QueuePosition = QueuePosition + 1 
                      WHERE UserID = ? AND QueuePosition >= ? AND QueuePosition < ?";
            $db->myQuery($query, [$userId, $newPosition, $currentPosition]);
        } else {
            // Moving down in queue - shift other items up
            $query = "UPDATE TaskQueue SET QueuePosition = QueuePosition - 1 
                      WHERE UserID = ? AND QueuePosition > ? AND QueuePosition <= ?";
            $db->myQuery($query, [$userId, $currentPosition, $newPosition]);
        }

        // Update the task's position
        $query = "UPDATE TaskQueue SET QueuePosition = ? WHERE TaskID = ? AND UserID = ?";
        $db->myQuery($query, [$newPosition, $taskId, $userId]);

        return true;
    }

    // Getters
    public function getQueueId() { return $this->queueId; }
    public function getTaskId() { return $this->taskId; }
    public function getUserId() { return $this->userId; }
    public function getPostponedDate() { return $this->postponedDate; }
    public function getQueuePosition() { return $this->queuePosition; }

    /**
     * Lazy Loading: Task object is only loaded when explicitly requested
     * This prevents unnecessary database queries if the task data isn't needed
     */
    public function getTask() {
        if ($this->task === null && $this->taskId !== null) {
            $this->task = new Task($this->taskId);
        }
        return $this->task;
    }

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

    // Setters
    public function setTaskId($taskId, $autoSave = true) {
        $this->taskId = $taskId;
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

    public function setPostponedDate($postponedDate, $autoSave = true) {
        $this->postponedDate = $postponedDate;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setQueuePosition($queuePosition, $autoSave = true) {
        $this->queuePosition = $queuePosition;
        if ($autoSave) {
            $this->save();
        }
    }
}