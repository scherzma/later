<?php
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'message' => 'EZ',
        'data' => [] // Could list users in a real app
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}