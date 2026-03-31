<?php
// CORS (keep your existing block as-is)

$allowed_origin = "https://campusconnect-frontend-psi.vercel.app";

header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ USE ENV VARIABLES (Render) with Local Fallbacks!
$host = getenv("DB_HOST") ?: "localhost";
$port = getenv("DB_PORT") ?: "3306";
$dbname = getenv("DB_NAME") ?: "campus_connect";
$username = getenv("DB_USER") ?: "campus_user";
$password = getenv("DB_PASS") ?: "Campus@123";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $conn = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
?>