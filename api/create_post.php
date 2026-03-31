<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");

// Verify user
$userData = verifyToken();
$user_id = $userData->user_id;

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$content = $data["content"] ?? null;
$media_url = $data["media_url"] ?? null;

if (!$content) {
    echo json_encode([
        "status" => "error",
        "message" => "Content is required"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, media_url) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $content, $media_url]);

    echo json_encode([
        "status" => "success",
        "message" => "Post created"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>