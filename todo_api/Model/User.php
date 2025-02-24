<?php

require_once "./Model/todo_db.inc.php";
require_once "./Model/Task.php";

class User
{
    private $db;
    private $userId;
    private $username;
    private $passwordHash;
    private $email;
    private $role;
    private $currentStreak;
    private $bestStreak;
    private $lastCompletedDate;
    private $emailNotifications;

    public function __construct($id = null)
    {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id)
    {
        $query = "SELECT * FROM User WHERE UserID = ?";
        $this->db->myQuery($query, [$id]);
        $result = $this->db->gibZeilen();

        if (empty($result)) {
            return false;
        }

        $data = $result[0];
        $this->loadFromData($data);
        return true;
    }

    // Find user by username
    public static function findByUsername($username) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM User WHERE Username = ?";
        $db->myQuery($query, [$username]);
        $rows = $db->gibZeilen();

        if (count($rows) > 0) {
            $user = new User();
            $user->loadFromData($rows[0]);
            return $user;
        }

        return null;
    }

    // Find user by email
    public static function findByEmail($email) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM User WHERE Email = ?";
        $db->myQuery($query, [$email]);
        $rows = $db->gibZeilen();

        if (count($rows) > 0) {
            $user = new User();
            $user->loadFromData($rows[0]);
            return $user;
        }

        return null;
    }

    public static function getAll()
    {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM User";
        $db->myQuery($query, []);
        $rows = $db->gibZeilen();

        $users = [];
        foreach ($rows as $row) {
            $user = new User();
            $user->loadFromData($row);
            $users[] = $user;
        }
        return $users;
    }

    private function loadFromData($data)
    {
        $this->userId = $data['UserID'];
        $this->username = $data['Username'];
        $this->passwordHash = $data['PasswordHash'];
        $this->email = $data['Email'] ?? null;
        $this->role = $data['Role'];
        $this->currentStreak = $data['CurrentStreak'] ?? 0;
        $this->bestStreak = $data['BestStreak'] ?? 0;
        $this->lastCompletedDate = $data['LastCompletedDate'] ?? null;
        $this->emailNotifications = isset($data['EmailNotifications']) ? (bool)$data['EmailNotifications'] : true;
    }

    public function save()
    {
        if ($this->userId === null) {
            $query = "INSERT INTO User (Username, PasswordHash, Email, Role, CurrentStreak, BestStreak, LastCompletedDate, EmailNotifications) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->myQuery($query, [
                $this->username,
                $this->passwordHash,
                $this->email,
                $this->role ?? 'user',
                $this->currentStreak ?? 0,
                $this->bestStreak ?? 0,
                $this->lastCompletedDate,
                $this->emailNotifications ? 1 : 0
            ]);
            $this->userId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE User SET 
                      Username = ?, 
                      PasswordHash = ?, 
                      Email = ?, 
                      Role = ?, 
                      CurrentStreak = ?, 
                      BestStreak = ?, 
                      LastCompletedDate = ?, 
                      EmailNotifications = ? 
                      WHERE UserID = ?";
            $this->db->myQuery($query, [
                $this->username,
                $this->passwordHash,
                $this->email,
                $this->role,
                $this->currentStreak,
                $this->bestStreak,
                $this->lastCompletedDate,
                $this->emailNotifications ? 1 : 0,
                $this->userId
            ]);
        }
    }

    public function delete()
    {
        if ($this->userId !== null) {
            $query = "DELETE FROM User WHERE UserID = ?";
            $this->db->myQuery($query, [$this->userId]);
            $this->userId = null;
        }
    }

    /**
     * Get tasks by user ID
     *
     * @return array Array of Task objects
     */
    public function getTasks() {
        return Task::getTasksByUserId($this->userId);
    }

    /**
     * Get tasks in the user's queue
     *
     * @return array Array of Task objects in queue
     */
    public function getQueuedTasks() {
        if ($this->userId === null) {
            return [];
        }

        require_once "./Model/TaskQueue.php";
        return TaskQueue::getQueuedTasksByUser($this->userId);
    }

    /**
     * Get tasks that should be completed today to maintain the streak
     *
     * @return bool Whether the user needs to complete a task today
     */
    public function needsTaskForStreak() {
        if ($this->userId === null) {
            return false;
        }

        // If user completed a task today, they're good
        if ($this->lastCompletedDate === date('Y-m-d')) {
            return false;
        }

        // If user has a streak and last completed a task yesterday, they need a task today
        if ($this->currentStreak > 0 && $this->lastCompletedDate === date('Y-m-d', strtotime('-1 day'))) {
            return true;
        }

        // If user has no streak or has already missed days, they can still start a new streak
        return true;
    }

    /**
     * Get the next recommended task for the user
     *
     * @return Task|null A task or null if no tasks available
     */
    public function getNextRecommendedTask() {
        if ($this->userId === null) {
            return null;
        }

        // First, check if there's anything in the queue
        require_once "./Model/TaskQueue.php";
        $nextQueuedTask = TaskQueue::getNextTaskInQueue($this->userId);
        if ($nextQueuedTask) {
            return $nextQueuedTask;
        }

        // If queue is empty, find a task based on priority and due date
        $query = "SELECT TaskID FROM Task 
                  WHERE UserID = ? AND Finished = 0 
                  ORDER BY 
                    CASE Priority 
                        WHEN 'high' THEN 1 
                        WHEN 'medium' THEN 2 
                        WHEN 'low' THEN 3 
                    END, 
                    CASE 
                        WHEN EndDate IS NULL THEN 1
                        ELSE 0
                    END, 
                    EndDate ASC 
                  LIMIT 1";

        $this->db->myQuery($query, [$this->userId]);
        $result = $this->db->gibZeilen();

        if (empty($result)) {
            return null;
        }

        return new Task($result[0]['TaskID']);
    }

    /**
     * Get the count of unfinished tasks for the user
     *
     * @return int Number of unfinished tasks
     */
    public function getUnfinishedTasksCount() {
        if ($this->userId === null) {
            return 0;
        }

        $query = "SELECT COUNT(*) as count FROM Task WHERE UserID = ? AND Finished = 0";
        $this->db->myQuery($query, [$this->userId]);
        $result = $this->db->gibZeilen();

        return (int)$result[0]['count'];
    }

    /**
     * Get the count of tasks completed today
     *
     * @return int Number of tasks completed today
     */
    public function getTasksCompletedToday() {
        if ($this->userId === null) {
            return 0;
        }

        $query = "SELECT COUNT(*) as count FROM Task 
                  WHERE UserID = ? AND Finished = 1 
                  AND DATE(DateFinished) = CURDATE()";
        $this->db->myQuery($query, [$this->userId]);
        $result = $this->db->gibZeilen();

        return (int)$result[0]['count'];
    }

    /**
     * Get complete streak information including today's tasks
     *
     * @return array Streak data
     */
    public function getStreakInfo() {
        return [
            'currentStreak' => $this->getCurrentStreak(),
            'bestStreak' => $this->getBestStreak(),
            'lastCompletedDate' => $this->getLastCompletedDate(), // Optional now
            'pendingTasks' => $this->getUnfinishedTasksCount(),
            'tasksFinishedToday' => $this->getTasksCompletedToday() // Optional metric
        ];
    }

    /**
     * Updates the user's streak based on task completion
     * Note: This is now handled by the database trigger, but this method
     * can be used for manual updates or to simulate the trigger's behavior
     *
     * @return bool Whether the streak was updated
     */
    public function updateStreak($wasPostponed = false) {
        if ($this->userId === null) {
            return false;
        }

        if ($wasPostponed) {
            // Reset streak if the task was ever postponed
            $this->currentStreak = 0;
        } else {
            // Increment streak for a direct completion
            $this->currentStreak++;
        }

        // Update best streak if current exceeds it
        if ($this->currentStreak > $this->bestStreak) {
            $this->bestStreak = $this->currentStreak;
        }

        // No longer need LastCompletedDate for streak logic
        // Optionally remove it or keep it for other purposes
        $this->lastCompletedDate = date('Y-m-d'); // Still useful for tracking last activity
        $this->save();

        return true;
    }

    // Getters
    public function getUserId() { return $this->userId; }
    public function getUsername() { return $this->username; }
    public function getPasswordHash() { return $this->passwordHash; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getCurrentStreak() { return $this->currentStreak; }
    public function getBestStreak() { return $this->bestStreak; }
    public function getLastCompletedDate() { return $this->lastCompletedDate; }
    public function getEmailNotifications() { return $this->emailNotifications; }

    // Setters
    public function setUsername($username, $autoSave = true) {
        $this->username = $username;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setPassword($password, $autoSave = true) {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($autoSave) {
            $this->save();
        }
    }

    public function setEmail($email, $autoSave = true) {
        $this->email = $email;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setRole($role, $autoSave = true) {
        if (in_array($role, ['admin', 'user'])) {
            $this->role = $role;
            if ($autoSave) {
                $this->save();
            }
        } else {
            throw new InvalidArgumentException("Role must be 'admin' or 'user'");
        }
    }

    public function setCurrentStreak($streak, $autoSave = true) {
        $this->currentStreak = (int)$streak;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setBestStreak($streak, $autoSave = true) {
        $this->bestStreak = (int)$streak;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setLastCompletedDate($date, $autoSave = true) {
        $this->lastCompletedDate = $date;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setEmailNotifications($enabled, $autoSave = true) {
        $this->emailNotifications = (bool)$enabled;
        if ($autoSave) {
            $this->save();
        }
    }
}
?>