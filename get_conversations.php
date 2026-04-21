<?php
header("Content-Type: application/json");

require_once(__DIR__ . "/config/db.php");
require_once(__DIR__ . "/middleware/auth.php");

// Verify user
$userData = verifyToken();
$user_id = $userData->user_id;

try {
    // Get users that are connected (mutual or one-way) OR have chatted
    $query = "
        SELECT DISTINCT u.id, u.name, u.avatar_url, u.department
        FROM users u
        LEFT JOIN connections c1 ON c1.follower_id = ? AND c1.following_id = u.id
        LEFT JOIN connections c2 ON c2.following_id = ? AND c2.follower_id = u.id
        LEFT JOIN messages m ON (m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = ?)
        WHERE u.id != ? AND (c1.id IS NOT NULL OR c2.id IS NOT NULL OR m.id IS NOT NULL)
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Now augment with last message and unread count
    foreach ($contacts as &$contact) {
        $c_id = $contact['id'];
        
        // Get last message
        $msgStmt = $conn->prepare("SELECT message, created_at FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1");
        $msgStmt->execute([$user_id, $c_id, $c_id, $user_id]);
        $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);
        
        $contact['last_message'] = $lastMsg ? $lastMsg['message'] : null;
        $contact['last_interaction'] = $lastMsg ? $lastMsg['created_at'] : '2000-01-01 00:00:00';
        
        // Get unread count
        $unreadStmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $unreadStmt->execute([$c_id, $user_id]);
        $unread = $unreadStmt->fetch(PDO::FETCH_ASSOC);
        
        $contact['unread_count'] = $unread ? $unread['unread'] : 0;
    }

    // Sort by last interaction
    usort($contacts, function($a, $b) {
        return strtotime($b['last_interaction']) - strtotime($a['last_interaction']);
    });

    echo json_encode([
        "status" => "success",
        "data" => $contacts
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch conversations",
        "error" => $e->getMessage()
    ]);
}
?>
