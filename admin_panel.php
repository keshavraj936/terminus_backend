<?php
require_once(__DIR__ . "/config/db.php");
header("Content-Type: application/json");

define('ADMIN_SECRET', 'cc_admin_secret_2024_keshav');

function verifyAdminKey() {
    // OPTIONS preflight must be allowed through (db.php handles it)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') return;
    $key = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $key = $headers['X-Admin-Key'] ?? $headers['x-admin-key'] ?? '';
    }
    if (!$key) {
        $key = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
    }
    if ($key !== ADMIN_SECRET) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Forbidden: Invalid admin key."]);
        exit;
    }
}

// OPTIONS preflight already handled by db.php — just run key auth for real requests
verifyAdminKey();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';

// ─── GET requests ─────────────────────────────────────────────────────────────
if ($method === 'GET') {

    if ($action === 'stats') {
        $users_count = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
        $posts_count = $conn->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $comments_count = $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        $likes_count = $conn->query("SELECT COUNT(*) FROM likes")->fetchColumn();

        $top_dept = $conn->query("SELECT department, COUNT(*) as c FROM users WHERE role != 'admin' AND department IS NOT NULL GROUP BY department ORDER BY c DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $recent_posts = $conn->query("
            SELECT p.id, p.content, p.created_at, u.name as author, u.department
            FROM posts p JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "data" => [
            "users" => (int)$users_count,
            "posts" => (int)$posts_count,
            "comments" => (int)$comments_count,
            "likes" => (int)$likes_count,
            "top_dept" => $top_dept['department'] ?? 'N/A',
            "recent_posts" => $recent_posts
        ]]);
        exit;
    }

    if ($action === 'users') {
        $search = '%' . ($_GET['search'] ?? '') . '%';
        $stmt = $conn->prepare("
            SELECT id, name, email, department, section, year, batch, role, created_at
            FROM users
            WHERE role != 'admin'
            AND (name LIKE ? OR email LIKE ? OR department LIKE ?)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$search, $search, $search]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'posts') {
        $search = '%' . ($_GET['search'] ?? '') . '%';
        $stmt = $conn->prepare("
            SELECT p.id, p.content, p.media_url, p.created_at,
                   u.name as author_name, u.email as author_email, u.department,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p JOIN users u ON p.user_id = u.id
            WHERE p.content LIKE ? OR u.name LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$search, $search]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'mess') {
        $stmt = $conn->query("SELECT day, date, meal_type, items FROM mess_menu ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), meal_type");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
}

// ─── POST requests ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $act = $data['action'] ?? '';

    if ($act === 'delete_user') {
        $id = (int)($data['id'] ?? 0);
        if (!$id) { echo json_encode(["status" => "error", "message" => "Missing ID"]); exit; }
        $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "User deleted."]);
        exit;
    }

    if ($act === 'delete_post') {
        $id = (int)($data['id'] ?? 0);
        if (!$id) { echo json_encode(["status" => "error", "message" => "Missing ID"]); exit; }
        $conn->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
        echo json_encode(["status" => "success", "message" => "Post deleted."]);
        exit;
    }

    if ($act === 'upload_mess') {
        $menu = $data['menu'] ?? null;
        if (!$menu || !is_array($menu)) {
            echo json_encode(["status" => "error", "message" => "Invalid menu payload."]);
            exit;
        }
        $conn->beginTransaction();
        $conn->exec("DELETE FROM mess_menu");
        $stmt = $conn->prepare("INSERT INTO mess_menu (day, date, meal_type, items) VALUES (?, ?, ?, ?)");
        foreach ($menu as $day => $meals) {
            if (is_array($meals)) {
                $date = $meals['date'] ?? null;  // extract date field
                foreach ($meals as $meal_type => $items) {
                    if ($meal_type === 'date') continue;  // skip date key as a meal row
                    if (is_string($items)) {
                        $stmt->execute([$day, $date, $meal_type, $items]);
                    }
                }
            }
        }
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Mess routine updated."]);
        exit;
    }
}

echo json_encode(["status" => "error", "message" => "Unknown action."]);
?>
