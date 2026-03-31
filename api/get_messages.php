<?php
require_once("../config/db.php");
header("Content-Type: application/json");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");

// Verify user
$userData = verifyToken();
$user_id = $userData->user_id;

// Get other user id
$other_user_id = $_GET["user_id"] ?? null;

if (!$other_user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT * FROM messages
        WHERE 
            (sender_id = ? AND receiver_id = ?)
            OR
            (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");

    $stmt->execute([
        $user_id, $other_user_id,
        $other_user_id, $user_id
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $messages
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
