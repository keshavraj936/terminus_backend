<?php
require_once("../config/db.php");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");

require_once(__DIR__ . "/../config/db.php");

try {
    // Check if user is authenticated to get `has_liked` and community info
    $headers = getallheaders();
    $current_user_id = null;
    $user_dept = null;
    $user_sec = null;
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        $token = str_replace("Bearer ", "", $authHeader);
        $secret_key = "MY_SUPER_SECRET_KEY_1234567890_ABCDEF";
        try {
            require_once(__DIR__ . "/../vendor/autoload.php");
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $current_user_id = $decoded->user_id;

            $stmt_cu = $conn->prepare("SELECT department, section FROM users WHERE id = ?");
            $stmt_cu->execute([$current_user_id]);
            $current_user_info = $stmt_cu->fetch(PDO::FETCH_ASSOC);
            if ($current_user_info) {
                $user_dept = $current_user_info['department'];
                $user_sec = $current_user_info['section'];
            }
        } catch (Exception $e) {}
    }

    $sql = "
        SELECT p.id, p.content, p.media_url, p.created_at,
               u.id as user_id, u.name, u.department, u.avatar_url,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    ";
    
    if ($current_user_id) {
        $sql .= ", (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :uid) as has_liked ";
    } else {
        $sql .= ", 0 as has_liked ";
    }
    
    $sql .= "
        FROM posts p
        JOIN users u ON p.user_id = u.id
    ";

    if ($current_user_id && $user_dept) {
        $sql .= " WHERE u.department = :dept ";
        if ($user_sec) {
            $sql .= " AND u.section = :sec ";
        } else {
            $sql .= " AND u.section IS NULL ";
        }
    }

    $sql .= " ORDER BY p.created_at DESC ";

    $stmt = $conn->prepare($sql);
    
    if ($current_user_id) {
        $stmt->bindParam(':uid', $current_user_id, PDO::PARAM_INT);
        if ($user_dept) {
            $stmt->bindParam(':dept', $user_dept, PDO::PARAM_STR);
            if ($user_sec) {
                $stmt->bindParam(':sec', $user_sec, PDO::PARAM_STR);
            }
        }
    }
    
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast boolean appropriately for consistent frontend usage
    foreach ($posts as &$post) {
        $post['has_liked'] = (bool)$post['has_liked'];
        $post['likes_count'] = (int)$post['likes_count'];
        $post['comments_count'] = (int)$post['comments_count'];
    }

    echo json_encode([
        "status" => "success",
        "data" => $posts
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>