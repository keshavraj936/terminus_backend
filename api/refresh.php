<?php
require_once("../config/db.php");
require_once(__DIR__ . "/../vendor/autoload.php");
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../utils/response.php");

if (!isset($_COOKIE['refresh_token'])) {
    http_response_code(401);
    sendResponse("error", "No refresh token provided in secure cookies.");
    exit;
}

$refresh_token = $_COOKIE['refresh_token'];
$secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";

try {
    $decoded = JWT::decode($refresh_token, new Key($secret_key, 'HS256'));
    
    // Validate user still exists (Optional but highly recommended)
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$decoded->user_id]);
    if ($stmt->rowCount() === 0) {
        throw new Exception("User not found");
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate NEW 15-minute access token
    $access_payload = [
        "user_id" => $user["id"],
        "email" => $user["email"],
        "exp" => time() + (15 * 60)
    ];
    $access_jwt = JWT::encode($access_payload, $secret_key, 'HS256');

    sendResponse("success", "Token refreshed successfully", [
        "token" => $access_jwt
    ]);

} catch (Exception $e) {
    $is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $is_secure = true;
    }
    $samesite = $is_secure ? 'None' : 'Lax';
    
    // Erase tainted/expired refresh token immediately
    setcookie("refresh_token", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => $samesite
    ]);
    http_response_code(401);
    sendResponse("error", "Invalid refresh token: " . $e->getMessage());
}
?>
