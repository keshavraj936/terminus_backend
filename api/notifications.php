<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");

require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");
require_once(__DIR__ . "/../utils/response.php");

$userData = verifyToken();
$user_id = $userData->user_id;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $conn->prepare("
            SELECT n.id, n.type, n.reference_id, n.is_read, n.created_at,
                   u.id as actor_id, u.name as actor_name, u.avatar_url as actor_avatar
            FROM notifications n
            JOIN users u ON n.actor_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert is_read to boolean
        foreach ($notifications as &$notif) {
            $notif['is_read'] = (bool)$notif['is_read'];
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        sendResponse("success", "Notifications fetched", [
            "notifications" => $notifications,
            "unread_count" => $unread_count
        ]);
    } catch (PDOException $e) {
        sendResponse("error", $e->getMessage());
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (isset($input['notification_id'])) {
        // Mark single as read
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$input['notification_id'], $user_id]);
            sendResponse("success", "Notification marked as read");
        } catch (PDOException $e) {
            sendResponse("error", $e->getMessage());
        }
    } else {
        // Mark all as read
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
            $stmt->execute([$user_id]);
            sendResponse("success", "All notifications marked as read");
        } catch (PDOException $e) {
            sendResponse("error", $e->getMessage());
        }
    }
}
?>
