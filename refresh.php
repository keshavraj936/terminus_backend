<?php
require_once("../config/db.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
require_once(__DIR__ . "/../utils/response.php");

if (!isset($_COOKIE['refresh_token'])) {
    sendResponse("error", "No refresh token provided");
    exit;
}

$refresh_token = $_COOKIE['refresh_token'];
$secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";

try {
    $decoded = JWT::decode($refresh_token, new Key($secret_key, 'HS256'));
    $user_id = $decoded->user_id;

    $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() === 0) {
        sendResponse("error", "User not found");
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $access_payload = [
        "user_id" => $user["id"],
        "email" => $user["email"],
        "exp" => time() + (15 * 60)
    ];

    $access_jwt = JWT::encode($access_payload, $secret_key, 'HS256');

    sendResponse("success", "Token refreshed", [
        "token" => $access_jwt
    ]);

} catch (Exception $e) {
    sendResponse("error", "Invalid or expired refresh token");
}
?>
