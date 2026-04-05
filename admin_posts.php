<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");

try {
    $userData = verifyToken();
    $user_id = $userData->user_id;

    // Verify Admin Role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Admin access required."]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $conn->query("
            SELECT p.id, p.content, p.media_url, p.created_at, u.name as author_name, u.email as author_email
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $posts]);
        exit;
    } 
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['action']) && $data['action'] === 'delete_post') {
            $target_id = $data['post_id'] ?? null;
            if (!$target_id) {
                 echo json_encode(["status" => "error", "message" => "Missing post_id."]);
                 exit;
            }
            // Fetch post to check media if necessary, but DB cascading and cleanup is fine
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$target_id]);
            echo json_encode(["status" => "success", "message" => "Post permanently deleted."]);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
