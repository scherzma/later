<?php
require_once "./inc/auth.php";
require_once "./Model/Task.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ($method === 'GET') {
    $tasks = Task::getTasksByUserId($userId);
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
    }, $tasks);
    echo json_encode($tasksArray);
} elseif ($method === 'POST') {
    // Get POST data (assumes JSON input)
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['error' => 'No valid POST data received']);
        http_response_code(400); // Bad Request
        exit;
    }

    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;
    $endDate = $input['endDate'] ?? null;
    $priority = $input['priority'] ?? null;
    $location = $input['location'] ?? null;

    if (!$title) {
        echo json_encode(['error' => 'Title required']);
        http_response_code(400); // Bad Request
        exit;
    }

    $existingTask = Task::getTaskByUserId($userId, $title);
    if ($existingTask) {
        echo json_encode(['error' => 'Task Title already exists']);
        http_response_code(400); // Bad Request
        exit;
    }

    // Use the new fromClientInput method to safely create task
    // This prevents mass assignment vulnerabilities by only allowing whitelisted fields
    $task = Task::fromClientInput($input, $userId);
    $task->setFinished(false, false);
    $task->save();

    echo json_encode(['received' => $input]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>