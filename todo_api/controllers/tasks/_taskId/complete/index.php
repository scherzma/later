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
        echo json_encode(['error' => 'Task already completed']);
        exit;
    }

    // Mark task as complete with current timestamp
    $task->setFinished(true, false);
    $task->setDateFinished(date('Y-m-d H:i:s'), false);
    $task->save();

    // Check if the task has ever been postponed using our tracking methods
    $inQueue = TaskQueue::isTaskInQueue($taskId, $userId);
    $hasBeenPostponed = $task->hasBeenPostponed();
    $wasPostponed = $inQueue || $hasBeenPostponed;
    
    // Debug info
    error_log("Task {$taskId} complete check - In queue: " . ($inQueue ? 'Yes' : 'No') . 
              ", Has been postponed: " . ($hasBeenPostponed ? 'Yes' : 'No') . 
              ", Final postponed status: " . ($wasPostponed ? 'Yes' : 'No'));

    // Remove from queue if it was there
    TaskQueue::removeTaskFromQueue($taskId, $userId);

    // Update user streak based on whether it was postponed
    $user->updateStreak($wasPostponed);

    // Return response with updated streak info
    echo json_encode([
        'message' => 'Task completed successfully',
        'taskId' => $task->getTaskId(),
        'title' => $task->getTitle(),
        'streakInfo' => [
            'currentStreak' => $user->getCurrentStreak(),
            'bestStreak' => $user->getBestStreak(),
            'wasPostponed' => $wasPostponed
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>