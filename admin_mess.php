<?php
require_once("../config/db.php");
header("Content-Type: application/json");

require_once(__DIR__ . "/../config/db.php");
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

    if ($method === 'POST') {
        $json_data = file_get_contents("php://input");
        $data = json_decode($json_data, true);
        
        if (!$data || !is_array($data)) {
             echo json_encode(["status" => "error", "message" => "Invalid JSON payload."]);
             exit;
        }

        // Begin Transaction
        $conn->beginTransaction();

        // Clear existing mess menu
        $conn->exec("TRUNCATE TABLE mess_menu");

        $stmt = $conn->prepare("INSERT INTO mess_menu (day, meal_type, items) VALUES (?, ?, ?)");

        // Iterate and insert
        foreach ($data as $day => $meals) {
            if (is_array($meals)) {
                foreach ($meals as $meal_type => $items) {
                    $stmt->execute([$day, $meal_type, $items]);
                }
            }
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Mess routine successfully updated."]);
        exit;
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
