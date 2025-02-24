<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/Tag.php";
require_once "./Model/User.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$tagId = $params['tagId'] ?? null;

// Authenticate user
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if (!$tagId) {
    http_response_code(400);
    echo json_encode(['error' => 'Tag ID required']);
    exit;
}

$tag = new Tag($tagId);
if (!$tag->getTagId()) {
    http_response_code(404);
    echo json_encode(['error' => 'Tag not found']);
    exit;
}

if ($tag->getUserId() !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

switch ($method) {
    case 'GET':
        // List all tasks for the tag using the model method
        $tasks = $tag->getTasks();
        $tasksArray = array_map(function ($task) {
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
        }, $tasks);
        echo json_encode($tasksArray);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>