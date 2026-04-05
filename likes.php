<?php
require_once("../config/db.php");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");
require_once(__DIR__ . "/../utils/response.php");

$userData = verifyToken();
$user_id = $userData->user_id;

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['post_id'])) {
    sendResponse("error", "post_id is required");
    exit;
}

$post_id = $input['post_id'];

try {
    // Check if post exists and get author
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        sendResponse("error", "Post not found");
        exit;
    }

    $post_author_id = $post['user_id'];

    // Check if like exists
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $existingLike = $stmt->fetch();

    if ($existingLike) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existingLike['id']]);
        $action = "unliked";
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        $action = "liked";

        // Create notification if not own post
        if ($post_author_id != $user_id) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, reference_id) VALUES (?, ?, 'like', ?)");
            $stmt->execute([$post_author_id, $user_id, $post_id]);
        }
    }

    // Get new count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    sendResponse("success", "Successfully $action post", ["likes_count" => $count, "has_liked" => ($action === "liked")]);

} catch (PDOException $e) {
    sendResponse("error", $e->getMessage());
}
?>
