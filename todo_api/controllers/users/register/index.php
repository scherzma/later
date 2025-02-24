<?php

// Future reference: Needs no authentication

require_once "./Model/User.php";

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(['error' => 'Method not allowed']);
    http_response_code(405); // Method Not Allowed

} elseif ($method === 'POST') {
    // Get POST data (assumes JSON input)
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $username = $input['username'] ?? null;
        $password = $input['password'] ?? null;

        if (!$username || !$password) {
            echo json_encode(['error' => 'Username and password required']);
            http_response_code(400); // Bad Request
            exit;
        }

        $existingUsers = User::findByUsername($username);
        if ($existingUsers) {
            echo json_encode(['error' => 'Username already exists']);
            http_response_code(400); // Bad Request
            exit;
        }

        $user = new User();
        $user->setUsername($username, false);
        $user->setPassword($password, false);
        $user->save();


        echo json_encode(['received' => $input]);

    } else {
        echo json_encode(['error' => 'No valid POST data received']);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
    http_response_code(405); // Method Not Allowed
}