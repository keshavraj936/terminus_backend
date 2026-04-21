<?php
require_once __DIR__ . '/config/db.php';

$sql = file_get_contents(__DIR__ . '/migration_chat.sql');

try {
    $conn->exec($sql);
    echo json_encode(['status' => 'success', 'message' => 'Chat migration ran successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
