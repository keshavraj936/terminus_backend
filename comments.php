<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");
require_once(__DIR__ . "/utils/response.php");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_GET['post_id'])) {
        sendResponse("error", "post_id is required");
        exit;
    }
    
    $post_id = $_GET['post_id'];
    
    try {
        $stmt = $conn->prepare("
            SELECT c.id, c.comment, c.created_at, u.name, u.avatar_url, u.id as user_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse("success", "Comments fetched", $comments);
    } catch (PDOException $e) {
        sendResponse("error", $e->getMessage());
    }
    exit;
}

if ($method === 'POST') {
    $userData = verifyToken();
    $user_id = $userData->user_id;

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['post_id']) || !isset($input['comment']) || trim($input['comment']) === '') {
        sendResponse("error", "post_id and comment are required");
        exit;
    }

    $post_id = $input['post_id'];
    $comment = trim($input['comment']);

    try {
        $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            sendResponse("error", "Post not found");
            exit;
        }

        $post_author_id = $post['user_id'];

        // Insert comment
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $comment]);
        $comment_id = $conn->lastInsertId();

        // Create notification
        if ($post_author_id != $user_id) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, reference_id) VALUES (?, ?, 'comment', ?)");
            $stmt->execute([$post_author_id, $user_id, $post_id]);
        }

        // Fetch newly created comment to return
        $stmt = $conn->prepare("
            SELECT c.id, c.comment, c.created_at, u.name, u.avatar_url, u.id as user_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$comment_id]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);

        sendResponse("success", "Comment added successfully", $newComment);

    } catch (PDOException $e) {
        sendResponse("error", $e->getMessage());
    }
}
?>
