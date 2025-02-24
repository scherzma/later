<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/Tag.php";
require_once "./Model/User.php";
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
        // List all tags for the task
        $tags = $task->getTags();
        $tagsArray = array_map(function ($tag) {
            return [
                'tagId' => $tag->getTagId(),
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
                'userId' => $tag->getUserId()
            ];
        }, $tags);
        echo json_encode($tagsArray);
        break;

    case 'POST':
        // Assign a tag to the task
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['tagId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag ID required']);
            exit;
        }

        $tagId = $input['tagId'];
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

        // Check if the tag is already assigned
        $existingTags = $task->getTags();
        if (array_reduce($existingTags, function ($carry, $t) use ($tagId) {
            return $carry || $t->getTagId() === $tagId;
        }, false)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag already assigned to task']);
            exit;
        }

        $task->addTag($tagId);
        echo json_encode([
            'message' => 'Tag assigned to task successfully',
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