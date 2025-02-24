<?php
require_once "./inc/auth.php";
require_once "./Model/Tag.php";
require_once "./Model/User.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Authenticate user for any request
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

switch ($method) {
    case 'GET':
        // List all tags for the authenticated user
        $tags = Tag::getTagsByUserId($userId);
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
        // Create a new tag for the authenticated user
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            exit;
        }

        $name = $input['name'] ?? null;
        $priority = $input['priority'] ?? 'medium';

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        // Check if tag name already exists for this user (handled by unique constraint in DB)
        $tag = new Tag();
        $tag->setName($name, false);
        try {
            $tag->setPriority($priority, false);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $tag->setUserId($userId, false);

        try {
            $tag->save();
            echo json_encode([
                'tagId' => $tag->getTagId(),
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
                'userId' => $tag->getUserId(),
                'message' => 'Tag created successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag name already exists for this user']);
            exit;
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>