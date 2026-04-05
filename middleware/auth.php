<?php
require_once(__DIR__ . "/vendor/autoload.php");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verifyToken() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Token not provided"
        ]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace("Bearer ", "", $authHeader);

    // Using the same 32+ character secret key you updated in login.php!
    $secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        return $decoded;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid or expired token"
        ]);
        exit;
    }
}
?>
