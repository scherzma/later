<?php
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$id = $params['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        echo json_encode([
            'status' => 'success',
            'message' => "User with ID: $id",
            'data' => ['id' => $id]
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID required']);
    }
}