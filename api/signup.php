<?php
require_once("../config/db.php");
header("Content-Type: application/json");



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

    echo json_encode([
        "status" => "success",
        "message" => "User created successfully"
    ]);
    exit;

} catch(PDOException $e) {
    sendResponse("error", $e->getMessage());
}
?>
