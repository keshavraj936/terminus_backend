<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");

// Verify user
$userData = verifyToken();
$sender_id = $userData->user_id;

// Get input
$data = json_decode(file_get_contents("php://input"), true);

$receiver_id = $data["receiver_id"] ?? null;
$message = $data["message"] ?? null;

if (!$receiver_id || !$message) {
    echo json_encode([
        "status" => "error",
        "message" => "receiver_id and message required"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$sender_id, $receiver_id, $message]);

    echo json_encode([
        "status" => "success",
        "message" => "Message sent"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
