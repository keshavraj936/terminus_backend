<?php
require_once("config/db.php");
require_once(__DIR__ . "/../vendor/autoload.php");
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/../utils/response.php");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

// Validation
if (!$email || !$password) {
    sendResponse("error", "Email and password required");
    exit;
}

try {
    // Find user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() === 0) {
        sendResponse("error", "User not found");
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if (!password_verify($password, $user["password"])) {
        sendResponse("error", "Invalid password");
        exit;
    }

    $secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";

    // 15-Minute Short-Lived Access Payload
    $access_payload = [
        "user_id" => $user["id"],
        "email" => $user["email"],
        "exp" => time() + (15 * 60)
    ];

    // 7-Day Long-Lived Refresh Payload
    $refresh_payload = [
        "user_id" => $user["id"],
        "exp" => time() + (7 * 24 * 60 * 60)
    ];

    $access_jwt = JWT::encode($access_payload, $secret_key, 'HS256');
    $refresh_jwt = JWT::encode($refresh_payload, $secret_key, 'HS256');

    // Detect if running on HTTPS (Production Render/Vercel) or HTTP (Localhost)
    $is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $is_secure = true;
    }
    
    // Cross-origin cookies strictly require SameSite=None + Secure=true
    $samesite = $is_secure ? 'None' : 'Lax';

    // Secure HttpOnly Cookie
    setcookie("refresh_token", $refresh_jwt, [
        'expires' => time() + (7 * 24 * 60 * 60),
        'path' => '/',
        'secure' => $is_secure, 
        'httponly' => true,
        'samesite' => $samesite
    ]);

    unset($user["password"]);

    sendResponse("success", "Login successful", [
        "user" => $user,
        "token" => $access_jwt // Send access token strictly via JSON body (invisible from storage)
    ]);

} catch (PDOException $e) {
    sendResponse("error", $e->getMessage());
}
?>
