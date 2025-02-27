<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/User.php";
require_once "./Model/TaskQueue.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$taskId = $params['taskId'] ?? null;

// Authenticate user
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if (!$taskId) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID required']);
    exit;
}

$task = new Task($taskId);
if (!$task->getTaskId()) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
    exit;
}

if ($task->getUserId() !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($method === 'POST') {
    // Check if task is already completed
    if ($task->getFinished()) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot postpone completed tasks']);
        exit;
    }

    // Check if already in queue - move to the end if it is
    if (TaskQueue::isTaskInQueue($taskId, $userId)) {
        // Remove from current position
        TaskQueue::removeTaskFromQueue($taskId, $userId);
        // It will be added back at the end below, so we don't need to exit here
    }

    // Add to queue
    $queueItem = TaskQueue::addTaskToQueue($taskId, $userId);

    // Reset streak if this was the next recommended task
    $nextTask = $user->getNextRecommendedTask();
    if ($nextTask && $nextTask->getTaskId() === $taskId) {
        $user->updateStreak(true); // Pass true to indicate postponement
    }

    // Get the next task to recommend - ensure it's not the same one
    $nextTask = $user->getNextRecommendedTask();
    $nextTaskData = null;
    
    // Make sure we don't recommend the same task that was just postponed
    if ($nextTask && $nextTask->getTaskId() !== $taskId) {
        $nextTaskData = [
            'taskId' => $nextTask->getTaskId(),
            'title' => $nextTask->getTitle(),
            'description' => $nextTask->getDescription(),
            'priority' => $nextTask->getPriority(),
            'endDate' => $nextTask->getEndDate(),
            'location' => $nextTask->getLocation()
        ];
    } else if ($nextTask && $nextTask->getTaskId() === $taskId) {
        // If somehow we still got the same task, try to get a different one
        // by removing it from queue temporarily
        TaskQueue::removeTaskFromQueue($taskId, $userId);
        $tempNextTask = $user->getNextRecommendedTask();
        
        if ($tempNextTask && $tempNextTask->getTaskId() !== $taskId) {
            $nextTaskData = [
                'taskId' => $tempNextTask->getTaskId(),
                'title' => $tempNextTask->getTitle(),
                'description' => $tempNextTask->getDescription(),
                'priority' => $tempNextTask->getPriority(),
                'endDate' => $tempNextTask->getEndDate(),
                'location' => $tempNextTask->getLocation()
            ];
        }
        
        // Add back to queue if we had to remove it
        if (!TaskQueue::isTaskInQueue($taskId, $userId)) {
            TaskQueue::addTaskToQueue($taskId, $userId);
        }
    }

    echo json_encode([
        'message' => 'Task postponed successfully',
        'taskId' => $task->getTaskId(),
        'title' => $task->getTitle(),
        'queuePosition' => $queueItem->getQueuePosition(),
        'nextTask' => $nextTaskData,
        'streakInfo' => [
            'currentStreak' => $user->getCurrentStreak(),
            'bestStreak' => $user->getBestStreak()
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>