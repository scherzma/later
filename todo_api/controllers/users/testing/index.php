<?php
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'message' => 'This is the users/something endpoint',
        'data' => [
            'resource' => 'something',
            'description' => 'A specific subsection of users'
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}