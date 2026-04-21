<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");

// Verify user
$userData = verifyToken();
$user_id = $userData->user_id;

$other_user_id = $_GET['user_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0; // for polling optimization

if (!$other_user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Target user ID is required"
    ]);
    exit;
}

try {
    // Mark messages as read if the current user is the receiver
    $updateStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $updateStmt->execute([$user_id, $other_user_id]);

    // Fetch messages
    $query = "SELECT * FROM messages 
              WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
              AND id > ?
              ORDER BY created_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $messages
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch messages",
        "error" => $e->getMessage()
    ]);
}
?>
