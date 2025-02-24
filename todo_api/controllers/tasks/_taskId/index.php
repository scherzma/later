<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
require_once "./Model/User.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$taskId = $params['taskId'] ?? null;

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

if ($method === 'GET') {
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
    echo json_encode([
        'taskId' => $task->getTaskId(),
        'title' => $task->getTitle(),
        'description' => $task->getDescription(),
        'endDate' => $task->getEndDate(),
        'priority' => $task->getPriority(),
        'location' => $task->getLocation(),
        'locationId' => $task->getLocationId(),
        'finished' => $task->getFinished()
    ]);
} elseif ($method === 'PUT') {
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

    // Get PUT data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid PUT data received']);
        exit;
    }

    // Update task with provided fields
    if (isset($input['title'])) {
        // Check if the title already exists for this user (excluding current task)
        $existingTask = Task::getTaskByUserId($userId, $input['title']);
        if ($existingTask && $existingTask->getTaskId() != $taskId) {
            http_response_code(400);
            echo json_encode(['error' => 'Task title already exists']);
            exit;
        }
        $task->setTitle($input['title'], false);
    }

    if (isset($input['description'])) {
        $task->setDescription($input['description'], false);
    }

    if (isset($input['endDate'])) {
        $task->setEndDate($input['endDate'], false);
    }

    if (isset($input['priority'])) {
        try {
            $task->setPriority($input['priority'], false);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    if (isset($input['location'])) {
        $task->setLocation($input['location'], false);
    }

    if (isset($input['locationId'])) {
        $task->setLocationId($input['locationId'], false);
    }

    if (isset($input['finished'])) {
        $task->setFinished($input['finished'], false);
    }

    // Save all changes at once
    $task->save();

    // Return updated task data
    echo json_encode([
        'taskId' => $task->getTaskId(),
        'title' => $task->getTitle(),
        'description' => $task->getDescription(),
        'endDate' => $task->getEndDate(),
        'priority' => $task->getPriority(),
        'location' => $task->getLocation(),
        'locationId' => $task->getLocationId(),
        'finished' => $task->getFinished(),
        'message' => 'Task updated successfully'
    ]);
} elseif ($method === 'DELETE') {
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

    // Get all associated data before deletion (for response or cleanup)
    $taskInfo = [
        'taskId' => $task->getTaskId(),
        'title' => $task->getTitle(),
        'finished' => $task->getFinished()
    ];

    // Handle associated objects (optional): reminders, tags, etc.
    // This will depend on the business logic requirements

    // Example: Get and delete reminders
    // $reminders = $task->getReminders();
    // Most database tables should have cascading deletes set up,
    // so this might not be necessary, but for completeness:
    /*
    foreach ($reminders as $reminder) {
        $reminder->delete();
    }
    */

    // Delete the task
    $task->delete();

    // Return success response
    echo json_encode([
        'message' => 'Task deleted successfully',
        'task' => $taskInfo
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>