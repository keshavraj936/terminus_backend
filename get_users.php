<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");

$userData = verifyToken();
$user_id = $userData->user_id;

try {
    // 1. Get current user's department and section
    $stmt_cu = $conn->prepare("SELECT department, section FROM users WHERE id = ?");
    $stmt_cu->execute([$user_id]);
    $current_user_info = $stmt_cu->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user_info) {
         echo json_encode(["status" => "error", "message" => "User not found"]);
         exit;
    }
    
    $user_dept = $current_user_info['department'];
    $user_sec = $current_user_info['section'];

    $scope = $_GET['scope'] ?? 'local';

    // 2. Fetch users
    $sql = "
        SELECT u.id, u.name, u.department, u.section, u.avatar_url,
               (SELECT COUNT(*) FROM connections c WHERE c.follower_id = :uid AND c.following_id = u.id) as is_following
        FROM users u 
        WHERE u.id != :uid AND (u.role != 'admin' OR u.role IS NULL)
    ";

    if ($scope === 'local') {
        if ($user_dept) {
            $sql .= " AND u.department = :dept ";
            if ($user_sec) {
                $sql .= " AND u.section = :sec ";
            } else {
                $sql .= " AND u.section IS NULL ";
            }
        } else {
            $sql .= " AND u.department IS NULL ";
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
    
    if ($scope === 'local') {
        if ($user_dept) {
            $stmt->bindParam(':dept', $user_dept, PDO::PARAM_STR);
            if ($user_sec) {
                $stmt->bindParam(':sec', $user_sec, PDO::PARAM_STR);
            }
        }
    }

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as &$u) {
        $u['is_following'] = (bool)$u['is_following'];
    }

    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
