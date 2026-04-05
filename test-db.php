<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once("config/db.php");

$stmt = $conn->query("SELECT * FROM users");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
