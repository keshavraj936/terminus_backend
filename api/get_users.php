<?php
require_once("../config/db.php");
header("Content-Type: application/json");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");

$userData = verifyToken();
$user_id = $userData->user_id;

try {
    $stmt = $conn->prepare("SELECT id, name, department FROM users WHERE id != ?");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
