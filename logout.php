<?php
require_once("config/db.php");
header("Content-Type: application/json");
require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/../utils/response.php");

// Instantly delete the HttpOnly Refresh Token in the user's browser
$is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $is_secure = true;
}
$samesite = $is_secure ? 'None' : 'Lax';

setcookie("refresh_token", "", [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $is_secure,
    'httponly' => true,
    'samesite' => $samesite
]);

sendResponse("success", "Logged out securely");
?>
