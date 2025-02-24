<?php
require_once "./Model/User.php";
require_once "./inc/jwt.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;
    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }
    $user = User::findByUsername($username);
    if (!$user || !password_verify($password, $user->getPasswordHash())) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    $token = generateJWT($user->getUserId());
    echo json_encode(['token' => $token]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>