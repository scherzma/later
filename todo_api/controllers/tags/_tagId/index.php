<?php
require_once "./inc/auth.php";
require_once "./Model/Tag.php";
require_once "./Model/User.php";
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$params = $_REQUEST['params'] ?? [];
$tagId = $params['tagId'] ?? null;

// Authenticate user for any request
$userId = requireAuth();
$user = new User($userId);
if (!$user->getUserId()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if (!$tagId) {
    http_response_code(400);
    echo json_encode(['error' => 'Tag ID required']);
    exit;
}

$tag = new Tag($tagId);
if (!$tag->getTagId()) {
    http_response_code(404);
    echo json_encode(['error' => 'Tag not found']);
    exit;
}

if ($tag->getUserId() !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

switch ($method) {
    case 'GET':
        // Return tag details
        echo json_encode([
            'tagId' => $tag->getTagId(),
            'name' => $tag->getName(),
            'priority' => $tag->getPriority(),
            'userId' => $tag->getUserId()
        ]);
        break;

    case 'PUT':
        // Update tag
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            exit;
        }

        if (isset($input['name'])) {
            $tag->setName($input['name'], false);
        }
        if (isset($input['priority'])) {
            try {
                $tag->setPriority($input['priority'], false);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
        }

        try {
            $tag->save();
            echo json_encode([
                'tagId' => $tag->getTagId(),
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
                'userId' => $tag->getUserId(),
                'message' => 'Tag updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag name already exists for this user']);
            exit;
        }
        break;

    case 'DELETE':
        // Delete tag
        $tagInfo = [
            'tagId' => $tag->getTagId(),
            'name' => $tag->getName()
        ];
        $tag->delete();
        echo json_encode([
            'message' => 'Tag deleted successfully',
            'tag' => $tagInfo
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>