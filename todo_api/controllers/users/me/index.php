<?php
require_once "./inc/auth.php";
require_once "./Model/User.php";
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = requireAuth();
$user = new User($userId);

if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

switch ($method) {
    case 'GET':
        // Return user profile
        echo json_encode([
            'userId' => $user->getUserId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole()
        ]);
        break;

    case 'PUT':
        // Update user profile
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            exit;
        }

        // Handle username update if provided
        if (isset($input['username'])) {
            // Check if username already exists (but isn't the current user)
            $existingUser = User::findByUsername($input['username']);
            if ($existingUser && $existingUser->getUserId() != $userId) {
                http_response_code(400);
                echo json_encode(['error' => 'Username already exists']);
                exit;
            }
            $user->setUsername($input['username'], false);
        }

        // Handle password update if provided
        if (isset($input['password']) && !empty($input['password'])) {
            $user->setPassword($input['password'], false);
        }

        // Save changes
        $user->save();

        echo json_encode([
            'userId' => $user->getUserId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'message' => 'User updated successfully'
        ]);
        break;

    case 'DELETE':
        // Delete user account
        // First we should check if there are any dependencies (like tasks)

        /*
        $tasks = $user->getTasks();
        if (count($tasks) > 0) {
            // Option 1: Prevent deletion if user has tasks
            http_response_code(400);
            echo json_encode([
                'error' => 'Cannot delete user with existing tasks',
                'taskCount' => count($tasks)
            ]);
            exit;

            // Option 2 (alternative): Delete all user's tasks first
            // Uncomment the following code to use this option instead
            /*
            foreach ($tasks as $task) {
                $task->delete();
            }
        }
        */


        // Now delete the user
        $user->delete();

        echo json_encode([
            'message' => 'User deleted successfully'
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>