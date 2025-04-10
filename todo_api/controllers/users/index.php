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

if ($user->getRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}


if ($method === 'GET') {
    $users = User::getAll();

    // Convert User objects to array
    $usersArray = array_map(function($user) {
        return [
            'userId' => $user->getUserId(),
            'username' => $user->getUsername(),
            'passwordHash' => $user->getPasswordHash(),
            'role' => $user->getRole()
            // Note: passwordHash can be excluded for security
        ];
    }, $users);

    echo json_encode($usersArray, JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}