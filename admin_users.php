<?php
require_once("config/db.php");
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");

try {
    $userData = verifyToken();
    $user_id = $userData->user_id;

    // Verify Admin Role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Admin access required."]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $conn->query("SELECT id, name, email, department, section, role, created_at FROM users WHERE role != 'admin' OR role IS NULL ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $users]);
        exit;
    } 
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['action']) && $data['action'] === 'delete_user') {
            $target_id = $data['user_id'] ?? null;
            if (!$target_id) {
                 echo json_encode(["status" => "error", "message" => "Missing user_id."]);
                 exit;
            }
            if ($target_id == $user_id) {
                echo json_encode(["status" => "error", "message" => "Cannot delete yourself."]);
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_id]);
            echo json_encode(["status" => "success", "message" => "User permanently deleted."]);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
