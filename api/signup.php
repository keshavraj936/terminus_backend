<?php
require_once("../config/db.php");
header("Content-Type: application/json");


use Firebase\JWT\JWT;

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../utils/response.php");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

$name = $data["name"] ?? null;
$email = $data["email"] ?? null;
$password = $data["password"] ?? null;
$department = $data["department"] ?? null;
$year = $data["year"] ?? null;
$batch = $data["batch"] ?? null;

// Validation
if (!$name || !$email || !$password) {
    sendResponse("error", "Missing required fields");
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        sendResponse("error", "User already exists");
        exit;
    }

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, department, year, batch)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $email,
        $hashedPassword,
        $department,
        $year,
        $batch
    ]);

    $user_id = $conn->lastInsertId();

    $secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";
    $access_jwt = JWT::encode(["user_id" => $user_id, "email" => $email, "exp" => time() + (15 * 60)], $secret_key, 'HS256');
    $refresh_jwt = JWT::encode(["user_id" => $user_id, "exp" => time() + (7 * 24 * 60 * 60)], $secret_key, 'HS256');

    $is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $is_secure = true;
    }
    $samesite = $is_secure ? 'None' : 'Lax';

    setcookie("refresh_token", $refresh_jwt, [
        'expires' => time() + (7 * 24 * 60 * 60),
        'path' => '/',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => $samesite
    ]);

    sendResponse("success", "User registered successfully", [
        "user" => [
            "id" => $user_id,
            "name" => $name,
            "email" => $email,
            "department" => $department,
            "year" => $year,
            "batch" => $batch,
            "avatar_url" => null
        ],
        "token" => $access_jwt
    ]);

} catch(PDOException $e) {
    sendResponse("error", $e->getMessage());
}
?>
