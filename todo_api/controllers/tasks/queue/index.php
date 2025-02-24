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

switch ($method) {
    case 'GET':
        // Get all tasks in user's queue
        $queuedTasks = $user->getQueuedTasks();
        $tasksArray = array_map(function($task) {
            return [
                'taskId' => $task->getTaskId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'endDate' => $task->getEndDate(),
                'priority' => $task->getPriority(),
                'location' => $task->getLocation(),
                'locationId' => $task->getLocationId(),
                'finished' => $task->getFinished()
            ];
        }, $queuedTasks);

        echo json_encode($tasksArray);
        break;

    case 'POST':
        // Add a task to the queue
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['taskId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID required']);
            exit;
        }

        $taskId = $input['taskId'];
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

        if ($task->getFinished()) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot queue finished tasks']);
            exit;
        }

        // Check if task is already in queue
        if (TaskQueue::isTaskInQueue($taskId, $userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Task already in queue']);
            exit;
        }

        // Add to queue
        $queueItem = TaskQueue::addTaskToQueue($taskId, $userId);

        echo json_encode([
            'message' => 'Task added to queue successfully',
            'taskId' => $task->getTaskId(),
            'title' => $task->getTitle(),
            'queuePosition' => $queueItem->getQueuePosition()
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>