<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");
require_once(__DIR__ . "/utils/response.php");

$userData = verifyToken();

if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
    sendResponse("error", "No file uploaded or upload error.");
    exit;
}

$file = $_FILES['media'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowedTypes)) {
    sendResponse("error", "Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.");
    exit;
}

// Ensure uploads directory exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('media_', true) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Generate public URL relative to backend root
    $media_url = "/uploads/" . $filename;
    sendResponse("success", "File uploaded successfully", ["media_url" => $media_url]);
} else {
    sendResponse("error", "Failed to move uploaded file.");
}
?>
