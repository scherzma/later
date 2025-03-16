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
    private $lastLogin;
    private $failedAttempts;
    private $lastFailedLogin;
    private $lastActivityTime;

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
        $this->lastLogin = $data['LastLogin'] ?? null;
        $this->failedAttempts = $data['FailedAttempts'] ?? 0;
        $this->lastFailedLogin = $data['LastFailedLogin'] ?? null;
        $this->lastActivityTime = $data['LastActivityTime'] ?? null;
    }

    public function save()
    {
        if ($this->userId === null) {
            $query = "INSERT INTO User (Username, PasswordHash, Email, Role, CurrentStreak, BestStreak, LastCompletedDate, EmailNotifications, 
                      LastLogin, FailedAttempts, LastFailedLogin, LastActivityTime) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->myQuery($query, [
                $this->username,
                $this->passwordHash,
                $this->email,
                $this->role ?? 'user',
                $this->currentStreak ?? 0,
                $this->bestStreak ?? 0,
                $this->lastCompletedDate,
                $this->emailNotifications ? 1 : 0,
                $this->lastLogin,
                $this->failedAttempts ?? 0,
                $this->lastFailedLogin,
                $this->lastActivityTime
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
                      EmailNotifications = ?,
                      LastLogin = ?,
                      FailedAttempts = ?,
                      LastFailedLogin = ?,
                      LastActivityTime = ?
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
                $this->lastLogin,
                $this->failedAttempts,
                $this->lastFailedLogin,
                $this->lastActivityTime,
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
     * @param bool $eagerLoad Whether to eager load related objects (Location, User, Tags)
     * @return array Array of Task objects
     */
    public function getTasks($eagerLoad = false) {
        if (!$eagerLoad) {
            // Original lazy loading implementation
            return Task::getTasksByUserId($this->userId);
        }
        
        // Eager loading implementation - fetch tasks with all related data in minimal queries
        $db = Todo_DB::gibInstanz();
        
        // 1. Fetch all tasks with their basic data in one query
        $query = "SELECT * FROM Task WHERE UserID = ?";
        $db->myQuery($query, [$this->userId]);
        $tasksData = $db->gibZeilen();
        
        if (empty($tasksData)) {
            return [];
        }
        
        // Create Task objects and collect task IDs
        $tasks = [];
        $taskIds = [];
        foreach ($tasksData as $data) {
            $task = new Task();
            $task->loadFromData($data);
            $tasks[$data['TaskID']] = $task;
            $taskIds[] = $data['TaskID'];
        }
        
        // Nothing to eager load if no tasks found
        if (empty($taskIds)) {
            return [];
        }
        
        // 2. Eager load location data for all tasks in one query
        $locationIds = array_filter(array_column($tasksData, 'LocationID'));
        if (!empty($locationIds)) {
            $placeholders = str_repeat('?,', count($locationIds) - 1) . '?';
            $query = "SELECT * FROM Location WHERE LocationID IN ($placeholders)";
            $db->myQuery($query, $locationIds);
            $locationsData = $db->gibZeilen();
            
            // Create Location objects and associate with tasks
            $locations = [];
            foreach ($locationsData as $data) {
                $location = new Location();
                $location->loadFromData($data);
                $locations[$data['LocationID']] = $location;
            }
            
            // Manually set the location object on each task
            foreach ($tasks as $task) {
                $locationId = $task->getLocationId();
                if ($locationId && isset($locations[$locationId])) {
                    $task->setLocationObjDirect($locations[$locationId]);
                }
            }
        }
        
        // 3. Eager load all tags for these tasks in one query
        $placeholders = str_repeat('?,', count($taskIds) - 1) . '?';
        $query = "SELECT t.*, tt.TaskID FROM Tag t 
                 JOIN TaskTag tt ON t.TagID = tt.TagID 
                 WHERE tt.TaskID IN ($placeholders)";
        $db->myQuery($query, $taskIds);
        $tagsData = $db->gibZeilen();
        
        // Group tags by task
        $taskTags = [];
        foreach ($tagsData as $data) {
            $taskId = $data['TaskID'];
            if (!isset($taskTags[$taskId])) {
                $taskTags[$taskId] = [];
            }
            
            $tag = new Tag();
            $tag->loadFromData($data);
            $taskTags[$taskId][] = $tag;
        }
        
        // Set tags on each task
        foreach ($taskTags as $taskId => $tags) {
            if (isset($tasks[$taskId])) {
                $tasks[$taskId]->setTagsDirect($tags);
            }
        }
        
        // 4. Eager load all reminders for these tasks in one query
        $query = "SELECT * FROM TaskReminder WHERE TaskID IN ($placeholders)";
        $db->myQuery($query, $taskIds);
        $remindersData = $db->gibZeilen();
        
        // Group reminders by task
        $taskReminders = [];
        foreach ($remindersData as $data) {
            $taskId = $data['TaskID'];
            if (!isset($taskReminders[$taskId])) {
                $taskReminders[$taskId] = [];
            }
            
            $reminder = new TaskReminder();
            $reminder->loadFromData($data);
            $taskReminders[$taskId][] = $reminder;
        }
        
        // Set reminders on each task
        foreach ($taskReminders as $taskId => $reminders) {
            if (isset($tasks[$taskId])) {
                $tasks[$taskId]->setRemindersDirect($reminders);
            }
        }
        
        // Return tasks in simple array format (not keyed by ID)
        return array_values($tasks);
    }

    /**
     * Get tasks in the user's queue
     *
     * @param bool $eagerLoad Whether to eager load related objects (Location, Tags, Reminders)
     * @return array Array of Task objects in queue
     */
    public function getQueuedTasks($eagerLoad = false) {
        if ($this->userId === null) {
            return [];
        }

        require_once "./Model/TaskQueue.php";
        
        if (!$eagerLoad) {
            // Original lazy loading implementation
            return TaskQueue::getQueuedTasksByUser($this->userId);
        }
        
        // Eager loading implementation - uses the Task eager loading system
        $tasks = $this->getTasks(true); // Get all tasks with eager loading
        
        // Filter only the queued tasks
        $queuedTaskIds = [];
        $db = Todo_DB::gibInstanz();
        $query = "SELECT TaskID FROM TaskQueue WHERE UserID = ? ORDER BY QueuePosition ASC";
        $db->myQuery($query, [$this->userId]);
        $queuedTasksData = $db->gibZeilen();
        
        foreach ($queuedTasksData as $data) {
            $queuedTaskIds[] = $data['TaskID'];
        }
        
        // Return only tasks that are in the queue, maintaining queue order
        $queuedTasks = [];
        foreach ($queuedTaskIds as $taskId) {
            foreach ($tasks as $task) {
                if ($task->getTaskId() == $taskId) {
                    $queuedTasks[] = $task;
                    break;
                }
            }
        }
        
        return $queuedTasks;
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
     * @param int|null $excludeTaskId Optional task ID to exclude from recommendations
     * @param bool $eagerLoad Whether to eager load related objects (Location, Tags, Reminders)
     * @return Task|null A task or null if no tasks available
     */
    public function getNextRecommendedTask($excludeTaskId = null, $eagerLoad = false) {
        if ($this->userId === null) {
            return null;
        }

        // For eager loading, we'll take a different approach - first get all tasks with eager loading
        if ($eagerLoad) {
            // Get all tasks with eager loading
            $allTasks = $this->getTasks(true);
            
            // Get all queued tasks
            require_once "./Model/TaskQueue.php";
            $queueInfo = [];
            $db = Todo_DB::gibInstanz();
            $query = "SELECT TaskID, QueuePosition FROM TaskQueue 
                      WHERE UserID = ? ORDER BY QueuePosition ASC";
            $db->myQuery($query, [$this->userId]);
            $queuedTasksData = $db->gibZeilen();
            
            foreach ($queuedTasksData as $data) {
                $queueInfo[$data['TaskID']] = $data['QueuePosition'];
            }
            
            // First, try to get a task from the queue (respecting the exclude)
            foreach ($allTasks as $task) {
                $taskId = $task->getTaskId();
                
                // Skip if it's the excluded task or not in queue or finished
                if ($taskId == $excludeTaskId || !isset($queueInfo[$taskId]) || $task->getFinished()) {
                    continue;
                }
                
                // Found the first valid queued task
                return $task;
            }
            
            // If we get here, there's no valid task in queue (or only the excluded one is in queue)
            // Look for a task that's not in queue, not finished, and not excluded
            usort($allTasks, function($a, $b) {
                // Sort by priority
                $priorityValues = ['high' => 1, 'medium' => 2, 'low' => 3];
                $priorityA = $priorityValues[$a->getPriority()];
                $priorityB = $priorityValues[$b->getPriority()];
                
                if ($priorityA !== $priorityB) {
                    return $priorityA - $priorityB;
                }
                
                // Then by due date
                $endDateA = $a->getEndDate();
                $endDateB = $b->getEndDate();
                
                // Null dates should come last
                if ($endDateA === null && $endDateB === null) return 0;
                if ($endDateA === null) return 1;
                if ($endDateB === null) return -1;
                
                return strtotime($endDateA) - strtotime($endDateB);
            });
            
            // Find first task that's not excluded, not in queue, and not finished
            foreach ($allTasks as $task) {
                $taskId = $task->getTaskId();
                
                // Skip if it's excluded, in queue, or finished
                if ($taskId == $excludeTaskId || isset($queueInfo[$taskId]) || $task->getFinished()) {
                    continue;
                }
                
                // Found a valid task not in queue
                return $task;
            }
            
            // If we still didn't find a task and were excluding one, try again including it
            if ($excludeTaskId !== null) {
                foreach ($allTasks as $task) {
                    // Skip if it's in queue or finished
                    if (isset($queueInfo[$task->getTaskId()]) || $task->getFinished()) {
                        continue;
                    }
                    
                    // Found a valid task not in queue
                    return $task;
                }
            }
            
            // Nothing found
            return null;
        }
        
        // Original implementation for lazy loading
        require_once "./Model/TaskQueue.php";
        $nextQueuedTask = TaskQueue::getNextTaskInQueue($this->userId);
        
        // If there's a task in the queue and it's not the one we want to exclude
        if ($nextQueuedTask && $nextQueuedTask->getTaskId() && 
            ($excludeTaskId === null || $nextQueuedTask->getTaskId() != $excludeTaskId)) {
            // Make sure the task exists and is not finished
            if (!$nextQueuedTask->getFinished()) {
                return $nextQueuedTask;
            } else {
                // If the task in queue is already finished, remove it from queue
                TaskQueue::removeTaskFromQueue($nextQueuedTask->getTaskId(), $this->userId);
                // And try to get the next one recursively, still excluding the task
                return $this->getNextRecommendedTask($excludeTaskId);
            }
        }
        
        // If we have tasks in the queue but the first one is the excluded task,
        // let's get all the tasks from the queue and return the second one if available
        if ($nextQueuedTask && $excludeTaskId && $nextQueuedTask->getTaskId() == $excludeTaskId) {
            $allQueuedTasks = TaskQueue::getQueuedTasksByUser($this->userId);
            // If we have at least 2 tasks in queue, return the second one
            if (count($allQueuedTasks) >= 2) {
                return $allQueuedTasks[1]; // Index 1 is the second task
            }
        }

        // If queue is empty or only has the excluded task, find a non-queued task based on priority and due date
        $excludeClause = $excludeTaskId ? " AND TaskID != ? " : "";
        $params = $excludeTaskId ? [$this->userId, $excludeTaskId] : [$this->userId];
        
        $query = "SELECT TaskID FROM Task 
                  WHERE UserID = ? AND Finished = 0 " . $excludeClause . "
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

        $this->db->myQuery($query, $params);
        $result = $this->db->gibZeilen();

        if (empty($result)) {
            // If we couldn't find any non-excluded tasks, and we were excluding one,
            // try again without the exclusion as a fallback (better to show some task than none)
            if ($excludeTaskId !== null) {
                // Remove the exclusion clause and try again
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
            } else {
                return null;
            }
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

        // Debug output
        error_log("Updating streak for user {$this->userId}. Current streak: {$this->currentStreak}, Was postponed: " . ($wasPostponed ? 'true' : 'false'));

        if ($wasPostponed) {
            // Reset streak if the task was ever postponed
            error_log("RESETTING STREAK TO ZERO due to postponed task");
            $this->currentStreak = 0;
        } else {
            // Increment streak for a direct completion
            error_log("INCREMENTING STREAK from {$this->currentStreak} to " . ($this->currentStreak + 1));
            $this->currentStreak++;
        }

        // Update best streak if current exceeds it
        if ($this->currentStreak > $this->bestStreak) {
            $this->bestStreak = $this->currentStreak;
            error_log("New best streak achieved: {$this->bestStreak}");
        }

        // No longer need LastCompletedDate for streak logic
        // Optionally remove it or keep it for other purposes
        $this->lastCompletedDate = date('Y-m-d'); // Still useful for tracking last activity
        
        // Always persist the streak changes to the database
        $query = "UPDATE User SET 
                  CurrentStreak = ?, 
                  BestStreak = ?, 
                  LastCompletedDate = ? 
                  WHERE UserID = ?";
        $this->db->myQuery($query, [
            $this->currentStreak,
            $this->bestStreak,
            $this->lastCompletedDate,
            $this->userId
        ]);
        
        error_log("Final streak value after update: {$this->currentStreak}");
        return true;
    }

    /**
     * Records a successful login attempt
     */
    public function recordSuccessfulLogin() {
        $this->lastLogin = date('Y-m-d H:i:s');
        $this->failedAttempts = 0; // Reset failed attempts on successful login
        $this->lastActivityTime = date('Y-m-d H:i:s');
        
        $query = "UPDATE User SET 
                  LastLogin = ?, 
                  FailedAttempts = 0,
                  LastActivityTime = ?
                  WHERE UserID = ?";
        $this->db->myQuery($query, [
            $this->lastLogin,
            $this->lastActivityTime,
            $this->userId
        ]);
    }

    /**
     * Records a failed login attempt
     * 
     * @param string $username The username of the failed login
     * @return void
     */
    public static function recordFailedLogin($username) {
        $db = Todo_DB::gibInstanz();
        $user = self::findByUsername($username);
        
        if ($user) {
            $user->failedAttempts++;
            $user->lastFailedLogin = date('Y-m-d H:i:s');
            
            $query = "UPDATE User SET 
                      FailedAttempts = ?, 
                      LastFailedLogin = ? 
                      WHERE UserID = ?";
            $db->myQuery($query, [
                $user->failedAttempts,
                $user->lastFailedLogin,
                $user->userId
            ]);
        }
    }

    /**
     * Updates the user's last activity timestamp
     */
    public function updateActivity() {
        $this->lastActivityTime = date('Y-m-d H:i:s');
        
        $query = "UPDATE User SET LastActivityTime = ? WHERE UserID = ?";
        $this->db->myQuery($query, [
            $this->lastActivityTime,
            $this->userId
        ]);
    }

    /**
     * Creates a new session for the user
     * 
     * @param int $inactivityTimeout Timeout in seconds
     * @return string The session ID
     */
    public function createSession($inactivityTimeout = 1800) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $inactivityTimeout);
        
        $query = "INSERT INTO UserSession (SessionID, UserID, ExpiresAt) VALUES (?, ?, ?)";
        $this->db->myQuery($query, [
            $sessionId,
            $this->userId,
            $expiresAt
        ]);
        
        return $sessionId;
    }

    /**
     * Updates a user session's activity timestamp and expiration
     * 
     * @param string $sessionId The session ID
     * @param int $inactivityTimeout Timeout in seconds
     * @return bool Whether the session was updated
     */
    public static function updateSession($sessionId, $inactivityTimeout = 1800) {
        $db = Todo_DB::gibInstanz();
        $lastActivity = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + $inactivityTimeout);
        
        $query = "UPDATE UserSession SET LastActivity = ?, ExpiresAt = ? WHERE SessionID = ?";
        $db->myQuery($query, [
            $lastActivity,
            $expiresAt,
            $sessionId
        ]);
        
        return $db->rowCount() > 0;
    }

    /**
     * Checks if a session is valid and not expired
     * 
     * @param string $sessionId The session ID
     * @return int|null User ID if session is valid, null otherwise
     */
    public static function validateSession($sessionId) {
        $db = Todo_DB::gibInstanz();
        
        $query = "SELECT UserID FROM UserSession 
                  WHERE SessionID = ? AND ExpiresAt > NOW()";
        $db->myQuery($query, [$sessionId]);
        $result = $db->gibZeilen();
        
        if (!empty($result)) {
            return $result[0]['UserID'];
        }
        
        return null;
    }

    /**
     * Destroys a user session
     * 
     * @param string $sessionId The session ID
     * @return bool Whether the session was destroyed
     */
    public static function destroySession($sessionId) {
        $db = Todo_DB::gibInstanz();
        
        $query = "DELETE FROM UserSession WHERE SessionID = ?";
        $db->myQuery($query, [$sessionId]);
        
        return $db->rowCount() > 0;
    }

    /**
     * Benchmark function to determine optimal cost factor for password hashing
     * 
     * @param float $maxTime Maximum time in seconds (e.g., 0.35 for 350ms)
     * @return int The optimal cost factor
     */
    public static function findOptimalBcryptCost($maxTime = 0.35) {
        $cost = 10; // Start with default cost
        do {
            $startTime = microtime(true);
            password_hash('benchmark_test', PASSWORD_BCRYPT, ['cost' => $cost]);
            $endTime = microtime(true);
            $time = $endTime - $startTime;
            
            if ($time < $maxTime) {
                $cost++;
            }
        } while ($time < $maxTime);
        
        return $cost - 1; // Return the last cost that was under the maximum time
    }

    /**
     * Updates password hash if needed using the optimal cost factor
     * 
     * @param string $password The plain text password
     * @return bool Whether the password hash was updated
     */
    public function updatePasswordHashIfNeeded($password) {
        if (password_needs_rehash($this->passwordHash, PASSWORD_BCRYPT, ['cost' => self::findOptimalBcryptCost()])) {
            $this->setPassword($password);
            return true;
        }
        return false;
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
    public function getLastLogin() { return $this->lastLogin; }
    public function getFailedAttempts() { return $this->failedAttempts; }
    public function getLastFailedLogin() { return $this->lastFailedLogin; }
    public function getLastActivityTime() { return $this->lastActivityTime; }

    // Setters
    public function setUsername($username, $autoSave = true) {
        $this->username = $username;
        if ($autoSave) {
            $this->save();
        }
    }

    public function setPassword($password, $autoSave = true) {
        // Use the optimal cost factor for password hashing
        $cost = self::findOptimalBcryptCost();
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
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