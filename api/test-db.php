<?php
header("Content-Type: application/json");

// Correctly traverse up one directory to find config
require_once(__DIR__ . "/../config/db.php");

// If the script reaches this line, the PDO connection was established successfully
echo json_encode([
    "status" => "success",
    "message" => "Remote Database Connection Established Successfully!",
    "host" => getenv("DB_HOST") ?: "localhost (fallback)",
    "database" => getenv("DB_NAME") ?: "campus_connect (fallback)"
]);
?>
