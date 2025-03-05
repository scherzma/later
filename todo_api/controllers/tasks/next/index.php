<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/User.php";
require_once "./Model/TaskQueue.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Authenticate user
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ($method === 'GET') {
    // Check if we should exclude a specific task from the recommendation
    $excludeTaskId = isset($_GET['excludeTaskId']) ? $_GET['excludeTaskId'] : null;
    
    // Get the next recommended task, excluding the specified task if provided
    $nextTask = $user->getNextRecommendedTask($excludeTaskId);

    if (!$nextTask) {
        echo json_encode([
            'message' => 'No tasks available',
            'hasTask' => false
        ]);
        exit;
    }

    // Check if it's from the queue
    $isFromQueue = TaskQueue::isTaskInQueue($nextTask->getTaskId(), $userId);

    // Return task details
    echo json_encode([
        'hasTask' => true,
        'fromQueue' => $isFromQueue,
        'task' => [
            'taskId' => $nextTask->getTaskId(),
            'title' => $nextTask->getTitle(),
            'description' => $nextTask->getDescription(),
            'endDate' => $nextTask->getEndDate(),
            'priority' => $nextTask->getPriority(),
            'location' => $nextTask->getLocation(),
            'locationId' => $nextTask->getLocationId()
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>