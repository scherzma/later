<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/Tag.php";
require_once "./Model/User.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$taskId = $params['taskId'] ?? null;
$tagId = $params['tagId'] ?? null;

// Authenticate user
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if (!$taskId || !$tagId) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID and Tag ID required']);
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

$tag = new Tag($tagId);
if (!$tag->getTagId()) {
    http_response_code(404);
    echo json_encode(['error' => 'Tag not found']);
    exit;
}

if ($tag->getUserId() !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Tag does not belong to user']);
    exit;
}

switch ($method) {
    case 'DELETE':
        // Remove tag from task
        $existingTags = $task->getTags();
        if (!array_reduce($existingTags, function ($carry, $t) use ($tagId) {
            return $carry || $t->getTagId() === $tagId;
        }, false)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag not assigned to task']);
            exit;
        }

        $task->removeTag($tagId);
        echo json_encode([
            'message' => 'Tag removed from task successfully',
            'taskId' => $task->getTaskId(),
            'tagId' => $tag->getTagId()
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>