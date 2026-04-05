<?php
require_once("config/db.php");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/../middleware/auth.php");
require_once(__DIR__ . "/../utils/response.php");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_GET['user_id'])) {
        sendResponse("error", "user_id is required");
        exit;
    }
    
    $target_user_id = $_GET['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM connections WHERE following_id = ?");
        $stmt->execute([$target_user_id]);
        $followers_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM connections WHERE follower_id = ?");
        $stmt->execute([$target_user_id]);
        $following_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // If current user is logged in, check if they follow this user
        $is_following = false;
        if (isset(getallheaders()['Authorization'])) {
            try {
                $userData = verifyToken();
                $current_user_id = $userData->user_id;
                
                $stmt = $conn->prepare("SELECT id FROM connections WHERE follower_id = ? AND following_id = ?");
                $stmt->execute([$current_user_id, $target_user_id]);
                $is_following = $stmt->fetch() ? true : false;
            } catch (Exception $e) {
                // Ignore token errors for public profile fetch
            }
        }
        
        sendResponse("success", "Connection stats fetched", [
            "followers_count" => $followers_count,
            "following_count" => $following_count,
            "is_following" => $is_following
        ]);
        
    } catch (PDOException $e) {
        sendResponse("error", $e->getMessage());
    }
    exit;
}

if ($method === 'POST') {
    $userData = verifyToken();
    $user_id = $userData->user_id;

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['following_id'])) {
        sendResponse("error", "following_id is required");
        exit;
    }

    $following_id = $input['following_id'];
    
    if ($user_id == $following_id) {
        sendResponse("error", "You cannot follow yourself");
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$following_id]);
        if (!$stmt->fetch()) {
            sendResponse("error", "User not found");
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM connections WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $following_id]);
        $existingConnection = $stmt->fetch();

        if ($existingConnection) {
            // Unfollow
            $stmt = $conn->prepare("DELETE FROM connections WHERE id = ?");
            $stmt->execute([$existingConnection['id']]);
            $action = "unfollowed";
            $is_following = false;
        } else {
            // Follow
            $stmt = $conn->prepare("INSERT INTO connections (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $following_id]);
            $action = "followed";
            $is_following = true;

            // Notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow')");
            $stmt->execute([$following_id, $user_id]);
        }
        
        // Get new follower count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM connections WHERE following_id = ?");
        $stmt->execute([$following_id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        sendResponse("success", "Successfully $action user", [
            "followers_count" => $count,
            "is_following" => $is_following
        ]);

    } catch (PDOException $e) {
        sendResponse("error", $e->getMessage());
    }
}
?>
