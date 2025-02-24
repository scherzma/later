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

switch ($method) {
    case 'GET':
        // Check if task is in queue and get position
        $queueInfo = TaskQueue::getTaskQueueInfo($taskId, $userId);

        if (!$queueInfo) {
            http_response_code(404);
            echo json_encode(['error' => 'Task not in queue']);
            exit;
        }

        // Return queue info
        echo json_encode([
            'taskId' => $task->getTaskId(),
            'title' => $task->getTitle(),
            'queuePosition' => $queueInfo['QueuePosition'],
            'postponedDate' => $queueInfo['PostponedDate']
        ]);
        break;

    case 'PUT':
        // Update queue position
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['position'])) {
            http_response_code(400);
            echo json_encode(['error' => 'New position required']);
            exit;
        }

        $newPosition = (int)$input['position'];

        // Check if task is in queue
        if (!TaskQueue::isTaskInQueue($taskId, $userId)) {
            http_response_code(404);
            echo json_encode(['error' => 'Task not in queue']);
            exit;
        }

        // Reorder task
        $success = TaskQueue::reorderTask($taskId, $userId, $newPosition);

        if (!$success) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid position']);
            exit;
        }

        echo json_encode([
            'message' => 'Task position updated successfully',
            'taskId' => $task->getTaskId(),
            'title' => $task->getTitle(),
            'newPosition' => $newPosition
        ]);
        break;

    case 'DELETE':
        // Remove task from queue
        $success = TaskQueue::removeTaskFromQueue($taskId, $userId);

        if (!$success) {
            http_response_code(404);
            echo json_encode(['error' => 'Task not in queue']);
            exit;
        }

        echo json_encode([
            'message' => 'Task removed from queue successfully',
            'taskId' => $task->getTaskId(),
            'title' => $task->getTitle()
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>