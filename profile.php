<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");

// Verify token
$userData = verifyToken();

$user_id = $userData->user_id;

$method = $_SERVER['REQUEST_METHOD'];
$target_user_id = $user_id;

if ($method === 'GET' && isset($_GET['user_id'])) {
    $target_user_id = $_GET['user_id'];
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action']) && $data['action'] === 'update_info') {
        $stmt = $conn->prepare("UPDATE users SET about_me = ?, insta_link = ?, github_link = ? WHERE id = ?");
        $stmt->execute([
            $data['about_me'] ?? null,
            $data['insta_link'] ?? null,
            $data['github_link'] ?? null,
            $user_id
        ]);
        echo json_encode(["status" => "success", "message" => "Profile updated"]);
        exit;
    }

    if (isset($data['avatar_url'])) {
        $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$data['avatar_url'], $user_id]);
        echo json_encode(["status" => "success", "message" => "Avatar updated"]);
        exit;
    }
}

// Fetch user
$stmt = $conn->prepare("SELECT id, name, email, department, year, batch, section, role, about_me, insta_link, github_link, avatar_url FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $user
]);
?>
