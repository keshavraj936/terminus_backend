<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config/db.php';

try {
    $stmt = $conn->query("SELECT 1");
    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully 🚀"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
